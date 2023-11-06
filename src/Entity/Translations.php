<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="translations", indexes={
 *      @ORM\Index(name="IDX_TRANSLATION_OBJECT", columns={
 *          "locale", "object_class", "foreign_key"
 *      })
 * })
 */
class Translations extends AbstractTranslation
{
}
