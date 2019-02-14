<?php

namespace FriskMigrate\Domain\Voucher\Service;

use InvalidArgumentException;

class CodeGenerator
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $chars;

    /**
     * @var string
     */
    private $format;

    /**
     * @param array $config
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $config)
    {
        if (!$config['prefix']) {
            throw new InvalidArgumentException('Voucher prefix config missing');
        }
        if (!$config['avail_chars']) {
            throw new InvalidArgumentException('Available chars config missing');
        }
        if (!$config['format']) {
            throw new InvalidArgumentException('Code format config missing');
        }

        $this->prefix = $config['prefix'];
        $this->chars  = $config['avail_chars'];
        $this->format = $config['format'];
    }

    /**
     * @return string
     */
    public function generate()
    {
        $availChars = str_split($this->chars);
        $code = str_split($this->format);

        foreach ($code as $pos => $char) {
            if ($char === '?') {
                $code[$pos] = $availChars[mt_rand(0, count($availChars) - 1)];
            }
        }

        return $this->prefix . implode('', $code);
    }
}
