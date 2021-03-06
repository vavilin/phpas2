<?php

namespace models;

use AS2\MessageInterface;
use AS2\PartnerInterface;
use AS2\StorageInterface;

class FileStorage implements StorageInterface
{
    const TYPE_MESSAGE = 'message';
    const TYPE_PARTNER = 'partner';

    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param  array  $data
     * @return Message
     */
    public function initMessage($data = [])
    {
        return new Message($data);
    }

    /**
     * @param  string  $id
     * @return MessageInterface|false
     */
    public function getMessage($id)
    {
        $data = $this->loadEntity(self::TYPE_MESSAGE, $id);

        $message = new Message($data);
        $message->setSender($this->getPartner($message->getSenderId()));
        $message->setReceiver($this->getPartner($message->getReceiverId()));

        return $message;
    }

    /**
     * @param  Message|MessageInterface  $message
     * @return bool
     */
    public function saveMessage(MessageInterface $message)
    {
        $data = $message->getData();
        unset($data['receiver'], $data['receiver']);

        $path = $this->getEntityPath(self::TYPE_MESSAGE, $message->getMessageId());

        if ($headers = $message->getHeaders()) {
            file_put_contents(str_replace('.json', '.headers', $path), $headers);
        }

        if ($payload = $message->getPayload()) {
            file_put_contents(str_replace('.json', '.payload', $path), $payload);
        }

        if ($mdn = $message->getMdnPayload()) {
            file_put_contents(str_replace('.json', '.mdn', $path), $mdn);
        }

        if ($headers = $message->getHeaders()) {
            file_put_contents(str_replace('.json', '.txt', $path), $headers.PHP_EOL.$payload);
        }

        return $this->saveEntity($path, $message->getData());
    }

    /**
     * @param  array  $data
     * @return PartnerInterface
     */
    public function initPartner($data = [])
    {
        return new Partner($data);
    }

    /**
     * @param  string  $id
     * @return PartnerInterface
     *
     * @throws \RuntimeException
     */
    public function getPartner($id)
    {
        $data = $this->loadEntity(self::TYPE_PARTNER, $id);

        return new Partner($data);
    }

    /**
     * @param  PartnerInterface|Partner  $partner
     * @return bool
     */
    public function savePartner(PartnerInterface $partner)
    {
        $path = $this->getEntityPath(self::TYPE_PARTNER, $partner->getAs2Id());

        return $this->saveEntity($path, $partner->getData());
    }

    /**
     * @param  string  $type
     * @param  string  $id
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function loadEntity($type, $id)
    {
        $path = $this->getEntityPath($type, $id);

        if (! file_exists($path)) {
            throw new \RuntimeException(
                sprintf('Entity `%s:%s` not found.', $type, $id)
            );
        }

        $data = file_get_contents($this->getEntityPath($type, $id));
        $data = json_decode($data, true);

        if (empty($data)) {
            throw new \RuntimeException(
                sprintf('Invalid entity `%s:%s`.', $type, $id)
            );
        }

        return $data;
    }

    /**
     * @param  string  $path
     * @param  mixed  $data
     * @return boolean
     */
    protected function saveEntity($path, $data)
    {
        return (bool)file_put_contents($path, json_encode($data));
    }

    /**
     * @param  string  $type
     * @param  string  $id
     * @return string
     */
    protected function getEntityPath($type, $id)
    {
        return $this->path.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.strtolower($id).'.json';
    }
}
