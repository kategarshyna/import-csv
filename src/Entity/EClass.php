<?php

namespace App\Entity;

use App\Entity\interfaces\IEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EClass
 *
 * @ORM\Table(name="EClassTree", indexes={
 *     @ORM\Index(name="IDX_E_CLASS_NAME", columns={"PreferredName"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\EClassRepository")
 * @Gedmo\TranslationEntity(class="App\Entity\Translations")
 */
class EClass {
    public const ROOT_CODE = 100000000; // as long as e class is made of 4 levels
    public const AVAILABLE_LOCALES = [
        'de-CH',
        'fr-CH',
        'it-CH'
    ];

    /**
     * @ORM\Column(name="Code", type="integer", nullable=false, unique=true)
     * @ORM\Id
     */
    private int $code;

    /**
     * @ORM\Column(name="Version", type="string", length=50, nullable=false)
     */
    private string $version;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="PreferredName", type="string", length=255, nullable=false)
     */
    private string $preferredName;

    /**
     * @ORM\Column(name="ParentFK", type="integer", nullable=true)
     */
    private ?int $parentFK;

    public function __construct() {
        $this->code = 0;
        $this->preferredName = '';
        $this->parentFK = null;
    }

    public function getCode(): int {
        return $this->code;
    }

    /**
     * @param $code
     * @return EClass
     */
    public function setCode($code): EClass {
        $this->code = $code;

        return $this;
    }

    public function getPreferredName(): string {
        return $this->preferredName;
    }

    public function getCombinedName(): string {
        return $this->convertNumberToFormattedString($this->getCode()) . ' ' . $this->preferredName;
    }


    /**
     * Formats an eClass code to a string in the pattern xx-xx-xx-xx
     *
     * @param int $code
     * @return string
     */
    private function convertNumberToFormattedString(int $code) :string {
        $numberString = (string) $code;
        $length = strlen($numberString);

        $parts = array();
        for ($i = $length - 2; $i >= 0; $i -= 2) {
            $part = substr($numberString, $i, 2);

            if ($part !== '00') {
                array_unshift($parts, $part);
            }
        }

        return implode('-', $parts);
    }

    /**
     * @param $preferredName
     * @return EClass
     */
    public function setPreferredName($preferredName): EClass {
        $this->preferredName = $preferredName;

        return $this;
    }

    public function getParentFK(): ?int {
        return $this->parentFK;
    }

    /**
     * @param $parentFK
     * @return EClass
     */
    public function setParentFK($parentFK): EClass {
        $this->parentFK = $parentFK;

        return $this;
    }

    public function getVersion(): string {
        return $this->version;
    }

    /**
     * @param $version
     * @return EClass
     */
    public function setVersion($version): EClass {
        $this->version = $version;

        return $this;
    }

    public function getLevelModuloQuotient(): int {
        $quotient = 1;
        while ($this->code % $quotient === 0) {
            $quotient *= 100;
        }

        return (int)($quotient / 100);
    }
}
