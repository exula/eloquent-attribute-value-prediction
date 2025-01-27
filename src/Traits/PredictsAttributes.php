<?php

namespace DivineOmega\EloquentAttributeValuePrediction\Traits;

use DivineOmega\EloquentAttributeValuePrediction\Exceptions\ModelFileNotFound;
use DivineOmega\EloquentAttributeValuePrediction\Helpers\DatasetHelper;
use DivineOmega\EloquentAttributeValuePrediction\Helpers\PathHelper;
use Exception;
use InvalidArgumentException;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

trait PredictsAttributes
{
    public function predict(string $attribute)
    {
        $dataset = DatasetHelper::buildUnlabeledDataset($this, $attribute);

        $modelPath = PathHelper::getModelPath(get_class($this), $attribute);

        $estimator = PersistentModel::load(new Filesystem($modelPath));

        $prediction = $estimator->predict($dataset)[0];

        return $prediction;
    }

    public function getPredictions(string $attribute): array
    {
        if ($this->isAttributeContinuous($attribute)) {
            throw new InvalidArgumentException(
                'You can not get multiple predictions for a continuous (numeric) argument. Try using the `predict` method instead.'
            );
        }

        $dataset = DatasetHelper::buildUnlabeledDataset($this, $attribute);

        //If Model File is missing, throw a custom exception with a solution in it.
        $modelPath = PathHelper::getModelPath(get_class($this), $attribute);

        if(!is_file($modelPath))
        {
            throw new ModelFileNotFound(get_class($this));
        }

        $modelFile = new Filesystem($modelPath);

        $estimator = PersistentModel::load($modelFile);

        $predictions = $estimator->proba($dataset)[0];

        arsort($predictions);

        return $predictions;
    }

    public function isAttributeContinuous(string $attribute)
    {
        if (!$this->hasCast($attribute)) {
            throw new Exception('The attribute `'.$attribute.'` is missing from the model\'s `$casts` array.');
        }

        $castType = $this->getCastType($attribute);

        return in_array($castType, ['int', 'integer', 'real', 'float', 'double', 'decimal']);
    }

    public function registerEstimators(): array
    {
        return [];
    }
}