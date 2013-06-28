<?php
/**
 * Creates Packet objects
 *
 * PHP version 5.4
 *
 * @category   LibDNS
 * @package    Packets
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    2.0.0
 */
namespace LibDNS\Packets;

/**
 * Creates Packet objects
 *
 * @category   LibDNS
 * @package    Packets
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class PacketFactory
{
    /**
     * Create a new Packet object
     *
     * @return \LibDNS\Packets\Packet
     */
    public function create($data = '')
    {
        return new Packet(new LabelRegistry, $data);
    }
}