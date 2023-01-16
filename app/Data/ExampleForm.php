<?php
namespace App\Data;

use SlimEdge\Annotation\Entity\Accessor;
use SlimEdge\Annotation\Entity\Property;
use SlimEdge\Entity\AbstractForm;

/**
 * @property string $id
 */
class ExampleForm extends AbstractForm
{
    /**
     * @Accessor("id")
     */
    public function accessor($id)
    {
        return 'ID: ' . $id;
    }
}