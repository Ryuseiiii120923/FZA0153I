<?php

namespace App\Traits;

use App\Services\PpfService;

trait CalculatesGoodQty
{
    /**
     * Calculate GoodQty for a form and update the component arrays.
     *
     * @param string|int $formId
     * @return int|null
     */
    public function calcGoodQtyForForm($formId)
    {
        // Make sure the component has forms array
        if (!isset($this->forms[$formId])) return null;

        // Use the service
        $result = app(PpfService::class)->calculateGoodQtyForm($this->forms[$formId]);

        // Update component arrays if they exist
        if (property_exists($this, 'defectNg')) {
            $this->defectNg[$formId] = $result['defectNg'];
        }

        if (property_exists($this, 'reworkNg')) {
            $this->reworkNg[$formId] = $result['reworkNg'];
        }

        $this->forms[$formId]['GoodQty'] = $result['goodQty'];

        return $result['goodQty'];
    }
}