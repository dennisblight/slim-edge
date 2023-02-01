<?php
namespace App\Data;

use Respect\Validation\Validator as v;
use SlimEdge\Annotation\Entity\Validator;
use SlimEdge\Entity\AbstractForm;

/**
 * @property string $id
 */
class ExampleForm extends AbstractForm
{
    /**
     * @Validator("id") @param string $key
     */
    public function stringValidator($key)
    {
        return v::stringVal()->notBlank();
    }
}