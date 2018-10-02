<?php
/**
 * Import Definitions.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2016-2018 w-vision AG (https://www.w-vision.ch)
 * @license    https://github.com/w-vision/ImportDefinitions/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace ImportDefinitionsBundle\Interpreter;

use ImportDefinitionsBundle\Model\DefinitionInterface;
use ImportDefinitionsBundle\Model\Mapping;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Listing;

class ObjectResolver implements InterpreterInterface
{
    /**
     * {@inheritdoc}
     */
    public function interpret(
        Concrete $object,
        $value,
        Mapping $map,
        $data,
        DefinitionInterface $definition,
        $params,
        $configuration
    ) {
        if (!$value) {
            return $value;
        }

        $class = 'Pimcore\Model\DataObject\\' . $configuration['class'];
        $lookup = 'getBy' . ucfirst($configuration['field']);

        /**
         * @var Listing $listing
         */
        $listing = $class::$lookup($value);
        $listing->setUnpublished($configuration['match_unpublished']);
        $found = $listing->count();

        if ($found < 1) {
            $parent = \Pimcore\Model\DataObject::getByPath($configuration['object_path']);
            $key = \Pimcore\Model\Element\Service::getValidKey($value, 'object');            
            $object = new $class();
            $setter = 'set' . ucfirst($configuration['field']);
            $object->$setter($value);
            $object->setPublished(true);
            $object->setKey($key);
            $object->setParentId($parent->getId());
            foreach(explode(',',$configuration['additional_fields']) as $additionalField){
                $additionalField = explode('.',$additionalField);
                if(count($additionalField) > 1){
                    $setter = 'set' . ucfirst(trim($additionalField[0]));
                    $object->$setter($value, trim($additionalField[1]));
                }else{
                    $setter = 'set' . ucfirst(trim($additionalField[0]));
                    $object->$setter($value);
                }
            }
            $object->save();
            return $object;
        }
        else if($found > 1) {
            // too many found
            return null;
        }

        return $listing->current();
    }
}
