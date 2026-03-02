<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ConfirmDtehmMembership extends RowAction
{
    public $name = 'Confirm Payment';

    public function handle(Model $model, Request $request)
    {
        // Check if already confirmed
        if ($model->status === 'CONFIRMED') {
            return $this->response()->error('This membership payment is already confirmed.')->refresh();
        }

        try {
            // Confirm the membership
            $model->confirm(\Admin::user()->id);

            return $this->response()->success('DTEHM Membership confirmed successfully!')->refresh();
        } catch (\Exception $e) {
            return $this->response()->error('Error: ' . $e->getMessage())->refresh();
        }
    }

    public function dialog()
    {
        $this->confirm('Are you sure you want to confirm this DTEHM membership payment?');
    }

    public function authorize($user, $model)
    {
        // Only show confirm button for pending payments
        return $model->status === 'PENDING';
    }
}
