<?php

namespace Keesschepers\TrengoApi;

class User
{
    private $id;

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }
}
