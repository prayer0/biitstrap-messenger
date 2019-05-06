<?php
namespace App\Server;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use App\Entity\Meta;
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

class MessageServer implements MessageComponentInterface
{
    protected $connections = array();
    protected $container;
    protected $publicKey;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->publicKey = $this->em->getRepository('App\Entity\Meta')->findOneBy([
            'metakey' => 'pubkey'
        ]);

        if($this->publicKey){
            $this->publicKey = $this->publicKey->getMetaValue();
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

            $this->em->persist($publicKey_meta);
            $this->em->persist($privateKey_meta);
            $this->em->persist($address_meta);
            $this->em->flush();

            $this->publicKey = $publicKey->getHex();
        }
    }

    /**
     * A new websocket connection
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->connections[] = $conn;
        $conn->send('[Automatic message] Connection established: ' . getHostByName(getHostName()) . ':' . $this->container->getParameter('port'));
    }

    /**
     * Handle message sending
     *
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $messageData = trim($msg);
        foreach($this->connections as $conn){
            $conn->send($msg);
        }
    }

    /**
     * A connection is closed
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        foreach($this->connections as $key => $conn_element){
            if($conn === $conn_element){
                unset($this->connections[$key]);
                break;
            }
        }
    }

    /**
     * Error handling
     *
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send("Error : " . $e->getMessage());
        $conn->close();
    }

    /**
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param mixed $publicKey
     *
     * @return self
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}