<?php

namespace App\Support;

final class ChatConversationUuid
{
    private const NAMESPACE_UUID = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    public static function from(string $type, int $id, int $index = 0): string
    {
        $namespace = hex2bin(str_replace('-', '', self::NAMESPACE_UUID));
        $name = 'finwise/chat/'.$type.'/'.$id.($index > 0 ? '/'.$index : '');
        $hash = sha1($namespace.$name, true);
        $hash[6] = chr((ord($hash[6]) & 0x0F) | 0x50);
        $hash[8] = chr((ord($hash[8]) & 0x3F) | 0x80);
        $uuid = bin2hex(substr($hash, 0, 16));

        return substr($uuid, 0, 8).'-'.substr($uuid, 8, 4).'-'.substr($uuid, 12, 4).'-'.substr($uuid, 16, 4).'-'.substr($uuid, 20, 12);
    }
}
