<?php

namespace SimpleThings\EntityAudit;

class Revision
{
    private $rev;
    private $timestamp;
    private $username = null;
    private $comment = null;

    function __construct($rev, $timestamp, $username, $comment)
    {
        $this->rev = $rev;
        $this->timestamp = $timestamp;
        $this->username = $username;
        $this->comment = $comment;
    }

    public function getRev()
    {
        return $this->rev;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getComment()
    {
        return $this->comment;
    }
}