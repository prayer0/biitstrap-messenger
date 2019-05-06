<?php

namespace App\Model;

class InviteMessage
{
    private $from;
    private $type = 'invite';
    private $ip;
    private $port = 8080;
    private $challengeSignature;

    public function __construct(){
        $this->setIp(json_decode(file_get_contents('https://api6.ipify.org?format=json'), 1)['ip']);
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param mixed $from
     *
     * @return self
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     *
     * @return self
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     *
     * @return self
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChallengeSignature()
    {
        return $this->challengeSignature;
    }

    /**
     * @param mixed $challengeSignature
     *
     * @return self
     */
    public function setChallengeSignature($challengeSignature)
    {
        $this->challengeSignature = $challengeSignature;

        return $this;
    }

    public function toJson(){
        return json_encode([
            'from' => $this->getFrom(),
            'type' => $this->getType(), 
            'ip' => $this->getIp(), 
            'port' => $this->getPort(),
            'challenge_sig' => $this->getChallengeSignature()
        ]);    
    }
}