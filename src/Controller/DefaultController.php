<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use App\Entity\Meta;
use App\Model\InviteMessage;
use App\Model\AcceptMessage;
use App\Util\ECDH;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use Btccom\BitcoinCash\Transaction\SignatureHash\SigHash as BitcoinCashSigHash;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Script\ScriptFactory;
use Btccom\BitcoinCash\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Buffertools\Buffer;

use Elliptic\EC;
use Elliptic\EC\KeyPair;

class DefaultController extends Controller
{
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $publicKey = $em->getRepository('App\Entity\Meta')->findOneBy([
            'metakey' => 'pubkey'
        ]);

        if($publicKey){
            $publicKey = $publicKey->getMetaValue();
        }else{
            $random = new Random();
            $privKeyFactory = new PrivateKeyFactory();
            $privateKey = $privKeyFactory->generateCompressed($random);
            $publicKey = $privateKey->getPublicKey();

            $publicKey_meta = new Meta();
            $publicKey_meta
            ->setMetakey('pubkey')
            ->setMetavalue($publicKey->getHex());

            $privateKey_meta = new Meta();
            $privateKey_meta
            ->setMetakey('privkey')
            ->setMetavalue($privateKey->getHex());

            $address = new PayToPubKeyHashAddress($publicKey->getPubKeyHash());
            $address_meta = new Meta();
            $address_meta
            ->setMetakey('address')
            ->setMetavalue($address->getAddress());

            $em->persist($publicKey_meta);
            $em->persist($privateKey_meta);
            $em->persist($address_meta);
            $em->flush();

            $publicKey = $publicKey->getHex();
        }

        return $this->render('index.html.twig', [
            'publicKey' => $publicKey 
        ]);
    }

    public function invite(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ecdh = new ECDH();
        $privKey = $em->getRepository('App\Entity\Meta')->findOneBy([
            'metakey' => 'privkey'
        ]);
        $message = new InviteMessage();

        // bitcoin-lib
        $bitcoinCashNetwork = NetworkFactory::bitcoinCash();
        Bitcoin::setNetwork($bitcoinCashNetwork);
        $ecAdapter = Bitcoin::getEcAdapter();
        $privateKeyFactory = new PrivateKeyFactory($ecAdapter);
        $pubKeyFactory = new PublicKeyFactory();
        $addressCreator = new AddressCreator();
        $transactionBuilder = TransactionFactory::build();

        // prepare funding wallet
        $fundingPrivateKey = $privateKeyFactory->fromWif($this->getParameter('funding_wallet'));
        $fundingAddress = new PayToPubKeyHashAddress($fundingPrivateKey->getPublicKey()->getPubKeyHash());

        // sender & recipient, ecdh
        $ec = new EC('secp256k1');
        $myPrivateKey = $privateKeyFactory->fromHexCompressed($privKey->getMetavalue());
        $myPrivateKey_ecdh = $ec->keyFromPrivate($myPrivateKey->getBuffer()->getHex());
        $recipientPublicKey_hex = $request->get('recipient');
        $recipientPublicKey = new KeyPair($ec, ['pub' => $recipientPublicKey_hex, 'pubEnc' => 'hex']);
        $sharedSecret = $myPrivateKey_ecdh->derive($recipientPublicKey->getPublic())->toString(16);

        $message->setFrom($myPrivateKey->getPublicKey()->getBuffer()->getHex());
        $encryptedMessage = $ecdh->encrypt($message->toJson(), $sharedSecret);

        // get utxo from bitindex for funding the tx
        $bitindex = $this->get('app.guzzle.client.bitindex');
        $bitindex_result = $bitindex->request('GET', 'addrs/utxos?address=' . $fundingAddress->getAddress($bitcoinCashNetwork), [
                'api_key' => $this->getParameter('bitindex_apikey')
        ])->getBody()->getContents();

        if(($bitindex_result = json_decode($bitindex_result, 1)) !== false){
            if(!empty($bitindex_result['data'])){
                $utxo = $bitindex_result['data'][0];
            }             
        }

        // build tx
        // op_return
        $script = ScriptFactory::create()
        ->opcode(Opcodes::OP_RETURN)
        ->push(new Buffer('biitstrap'))
        ->push(new Buffer($myPrivateKey->getPublicKey()->getBuffer()->getHex()))
        ->push(new Buffer( (new PayToPubKeyHashAddress($pubKeyFactory->fromHex($recipientPublicKey_hex)->getPubKeyHash()))->getAddress($bitcoinCashNetwork) ))
        ->push(new Buffer($encryptedMessage))
        ->getScript();

        // set input
        $transactionBuilder
        ->input($utxo['txid'], $utxo['vout'])
        ->payToAddress($utxo['satoshis'] - (ceil(strlen($script->getHex())/2) + 350), $fundingAddress)
        ->output(0, $script)
        ;

        $tx = $transactionBuilder->get();
        $signatureChecker = \Btccom\BitcoinCash\Transaction\Factory\Checker\CheckerCreator::fromEcAdapter(\BitWasp\Bitcoin\Bitcoin::getEcAdapter());
        $signer = new Signer($tx, null, $signatureChecker);
        $sigHashType = BitcoinCashSigHash::BITCOINCASH | BitcoinCashSigHash::ALL;

        $in = new TransactionOutput($utxo['satoshis'], ScriptFactory::fromHex($utxo['scriptPubKey']));
        $signer->sign(0, $fundingPrivateKey, $in, null, $sigHashType);

        $signedTx = $signer->get();
        $signedTx_hex = $signer->get()->getHex();
        $signexTx_txid = $signedTx->getTxid()->getHex();

        try{
            $whatsonchain = $this->get('app.guzzle.client.whatsonchain');
            $whatsonchain_result = $whatsonchain->request('POST', 'broadcast', [
                'form_params' => [
                    'query' => $signedTx_hex
                ]
            ])->getBody()->getContents();

            return new JsonResponse([
                'form' => [
                    'isValid' => true,
                    'isSubmitted' => true
                ],
                'txid' => $signexTx_txid,
                'peer_pubkey' => $request->get('recipient')
            ]);
        }catch(Exception $e){
            return new JsonResponse([
                'form' => [
                    'isValid' => false,
                    'isSubmitted' => true,
                    'status' => false,
                    'exception' => 'tx-broadcast-error'
                ]
            ]);
        }
    }

    public function accept(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ecdh = new ECDH();
        $privKey = $em->getRepository('App\Entity\Meta')->findOneBy([
            'metakey' => 'privkey'
        ]);
        $message = new AcceptMessage();

        // bitcoin-lib
        $bitcoinCashNetwork = NetworkFactory::bitcoinCash();
        Bitcoin::setNetwork($bitcoinCashNetwork);
        $ecAdapter = Bitcoin::getEcAdapter();
        $privateKeyFactory = new PrivateKeyFactory($ecAdapter);
        $pubKeyFactory = new PublicKeyFactory();
        $addressCreator = new AddressCreator();
        $transactionBuilder = TransactionFactory::build();

        // prepare funding wallet
        $fundingPrivateKey = $privateKeyFactory->fromWif($this->getParameter('funding_wallet'));
        $fundingAddress = new PayToPubKeyHashAddress($fundingPrivateKey->getPublicKey()->getPubKeyHash());

        // sender & recipient, ecdh
        $ec = new EC('secp256k1');
        $myPrivateKey = $privateKeyFactory->fromHexCompressed($privKey->getMetavalue());
        $myPrivateKey_ecdh = $ec->keyFromPrivate($myPrivateKey->getBuffer()->getHex());
        $recipientPublicKey_hex = $request->get('recipient');
        $recipientPublicKey = new KeyPair($ec, ['pub' => $recipientPublicKey_hex, 'pubEnc' => 'hex']);
        $sharedSecret = $myPrivateKey_ecdh->derive($recipientPublicKey->getPublic())->toString(16);

        $message->setFrom($myPrivateKey->getPublicKey()->getBuffer()->getHex());
        $message->setReplyTo($request->get('txid'));
        $encryptedMessage = $ecdh->encrypt($message->toJson(), $sharedSecret);

        // get utxo from bitindex for funding the tx
        $bitindex = $this->get('app.guzzle.client.bitindex');
        $bitindex_result = $bitindex->request('GET', 'addrs/utxos?address=' . $fundingAddress->getAddress($bitcoinCashNetwork), [
                'api_key' => $this->getParameter('bitindex_apikey')
        ])->getBody()->getContents();

        if(($bitindex_result = json_decode($bitindex_result, 1)) !== false){
            if(!empty($bitindex_result['data'])){
                $utxo = $bitindex_result['data'][0];
            }             
        }

        // build tx
        // op_return
        $script = ScriptFactory::create()
        ->opcode(Opcodes::OP_RETURN)
        ->push(new Buffer('biitstrap'))
        ->push(new Buffer($myPrivateKey->getPublicKey()->getBuffer()->getHex()))
        ->push(new Buffer( (new PayToPubKeyHashAddress($pubKeyFactory->fromHex($recipientPublicKey_hex)->getPubKeyHash()))->getAddress($bitcoinCashNetwork) ))
        ->push(new Buffer($encryptedMessage))
        ->getScript();

        // set input
        $transactionBuilder
        ->input($utxo['txid'], $utxo['vout'])
        ->payToAddress($utxo['satoshis'] - (ceil(strlen($script->getHex())/2) + 350), $fundingAddress)
        ->output(0, $script)
        ;

        $tx = $transactionBuilder->get();
        $signatureChecker = \Btccom\BitcoinCash\Transaction\Factory\Checker\CheckerCreator::fromEcAdapter(\BitWasp\Bitcoin\Bitcoin::getEcAdapter());
        $signer = new Signer($tx, null, $signatureChecker);
        $sigHashType = BitcoinCashSigHash::BITCOINCASH | BitcoinCashSigHash::ALL;

        $in = new TransactionOutput($utxo['satoshis'], ScriptFactory::fromHex($utxo['scriptPubKey']));
        $signer->sign(0, $fundingPrivateKey, $in, null, $sigHashType);

        $signedTx = $signer->get();
        $signedTx_hex = $signer->get()->getHex();
        $signexTx_txid = $signedTx->getTxid()->getHex();

        try{
            $whatsonchain = $this->get('app.guzzle.client.whatsonchain');
            $whatsonchain_result = $whatsonchain->request('POST', 'broadcast', [
                'form_params' => [
                    'query' => $signedTx_hex
                ]
            ])->getBody()->getContents();

            return new JsonResponse([
                'form' => [
                    'isValid' => true,
                    'isSubmitted' => true
                ],
                'txid' => $signexTx_txid,
                'peer_pubkey' => $request->get('recipient')
            ]);
        }catch(Exception $e){
            return new JsonResponse([
                'form' => [
                    'isValid' => false,
                    'isSubmitted' => true,
                    'status' => false,
                    'exception' => 'tx-broadcast-error'
                ]
            ]);
        }
    }

    public function listen(Request $request){
        $response = new StreamedResponse();

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');

        $response->setCallback(function() {
            $request = $this->container->get('request_stack')->getCurrentRequest();

            clearstatcache();
            session_write_close();

            $ecdh = new ECDH();
            $bitdb = $this->get('app.guzzle.client.bitdb');
            $em = $this->getDoctrine()->getManager();
            $privKey = $em->getRepository('App\Entity\Meta')->findOneBy([
                'metakey' => 'privkey' 
            ])->getMetavalue();
            $recipientAddress = $em->getRepository('App\Entity\Meta')->findOneBy([
                'metakey' => 'address' 
            ])->getMetavalue();

            $query['v'] = 3;
            $query['q']['find'] = [
                'out.s1' => 'biitstrap',
                'out.s3' => $recipientAddress 
            ];
            $query['q']['sort'] = ['blk.i' => 1];
            $query['q']['limit'] = 10;
            $query['q']['db'] = ['u'];
            $query = base64_encode(json_encode($query));
            
            $response = $bitdb->request('GET', $query, [
                'headers' => [
                    'key' => $this->getParameter('bitdb_apikey')
                ]
            ])->getBody()->getContents();

            if( ($response = json_decode($response, 1)) !== false){
                if(empty($response['u'])){return;}                
                $tx = $response['u'][count($response['u'])-1];
                $encrypted = $tx['out'][1]['s4'];

                // bitcoin-lib
                $bitcoinCashNetwork = NetworkFactory::bitcoinCash();
                Bitcoin::setNetwork($bitcoinCashNetwork);
                $ecAdapter = Bitcoin::getEcAdapter();
                $privateKeyFactory = new PrivateKeyFactory($ecAdapter);
                $addressCreator = new AddressCreator();
                $transactionBuilder = TransactionFactory::build();

                // sender & recipient, ecdh
                $ec = new EC('secp256k1');

                $myPrivateKey = $privateKeyFactory->fromHexCompressed($privKey);
                $myPrivateKey_ecdh = $ec->keyFromPrivate($myPrivateKey->getBuffer()->getHex());
                $peerPublicKey = new KeyPair($ec, ['pub' => $tx['out'][1]['s2'], 'pubEnc' => 'hex']);

                $sharedSecret = $myPrivateKey_ecdh->derive($peerPublicKey->getPublic())->toString(16);
            }
            $data = json_decode($ecdh->decrypt($encrypted, $sharedSecret), 1);
            $data['txid'] = $tx['tx']['h'];
            $data = json_encode($data);
            $data .= "\n\n";
            
            echo "retry: 5000\n\n";
            echo "data: " . $data;
            
            ob_flush();
            flush();
        });

        $response->send();
        
        return $response;
    }
}