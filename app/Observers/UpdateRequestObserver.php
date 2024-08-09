<?php

namespace App\Observers;

use App\Models\Location;
use App\Models\Notification;
use App\Models\Organisation;
use App\Models\OrganisationEvent;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\UpdateRequest;
use App\UpdateRequest\OrganisationSignUpForm;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateRequestObserver
{
    /**
     * Handle to the update request "created" event.
     *
     * @throws \Exception
     */
    public function created(UpdateRequest $updateRequest): void
    {
        if ($updateRequest->isExisting()) {
            $this->handleExistingUpdateRequest($updateRequest);
        }

        if ($updateRequest->isNew()) {
            $this->handleNewUpdateRequest($updateRequest);
        }

        if ($this->ownerIsSuperAdmin($updateRequest)) {
            $updateRequest->apply($updateRequest->user);
        }
    }

    private function handleExistingUpdateRequest(UpdateRequest $updateRequest)
    {
        if (!$this->ownerIsSuperAdmin($updateRequest)) {
            $this->sendCreatedNotificationsForExisting($updateRequest);
        }

        $this->removeSameFieldsForPendingAndExisting($updateRequest);
        $this->deleteEmptyPendingForExisting($updateRequest);
    }

    private function handleNewUpdateRequest(UpdateRequest $updateRequest)
    {
        if (!$this->ownerIsSuperAdmin($updateRequest)) {
            $this->sendCreatedNotificationsForNew($updateRequest);
        }
    }

    private function ownerIsSuperAdmin(UpdateRequest $updateRequest)
    {
        return $updateRequest->user()->exists() ? $updateRequest->user->isSuperAdmin() : false;
    }

    /**
     * Removes the field present in the new update request from any
     * pending ones, for the same resource.
     */
    protected function removeSameFieldsForPendingAndExisting(UpdateRequest $updateRequest)
    {
        // Skip if there is no data in the update request.
        if (count($updateRequest->data) === 0) {
            return;
        }

        $data = Arr::dot($updateRequest->data);
        $dataKeys = array_keys($data);

        foreach ($dataKeys as &$dataKey) {
            // Delete entire arrays if provided.
            $dataKey = preg_replace('/\.([0-9]+)(.*)$/', '', $dataKey);

            // If a page content item is updated, then all other content updates on this page should be removed
            if ($updateRequest->updateable_type === 'pages' && str_ends_with($dataKey, '.content')) {
                $dataKey = 'content';
            }

            // Format for MySQL.
            $dataKey = "\"$.{$dataKey}\"";
        }

        $dataKeys = array_unique($dataKeys);
        $implodedDataKeys = implode(', ', $dataKeys);

        UpdateRequest::query()
            ->where('updateable_type', '=', $updateRequest->updateable_type)
            ->where('updateable_id', '=', $updateRequest->updateable_id)
            ->where('id', '!=', $updateRequest->id)
            ->existing()
            ->pending()
            ->update(['data' => DB::raw("JSON_REMOVE(`update_requests`.`data`, {$implodedDataKeys})")]);
    }

    /**
     * Soft deletes / rejects pending update requests that have empty
     * data objects. This is called after removing the same fields
     * for new update requests.
     */
    protected function deleteEmptyPendingForExisting(UpdateRequest $updateRequest)
    {
        // Uses JSON_DEPTH to determine if the data object is empty (depth of 1).
        UpdateRequest::query()
            ->where('updateable_type', '=', $updateRequest->updateable_type)
            ->where('updateable_id', '=', $updateRequest->updateable_id)
            ->whereRaw('JSON_DEPTH(`update_requests`.`data`) = ?', [1])
            ->existing()
            ->pending()
            ->delete();
    }

    /**
     * @throws \Exception
     */
    protected function sendCreatedNotificationsForExisting(UpdateRequest $updateRequest)
    {
        $resourceName = 'N/A';
        $resourceType = 'N/A';
        if ($updateRequest->getUpdateable() instanceof Location) {
            $resourceName = $updateRequest->getUpdateable()->address_line_1;
            $resourceType = 'location';
        } elseif ($updateRequest->getUpdateable() instanceof Service) {
            $resourceName = $updateRequest->getUpdateable()->name;
            $resourceType = 'service';
        } elseif ($updateRequest->getUpdateable() instanceof ServiceLocation) {
            $resourceName = $updateRequest->getUpdateable()->name ?? $updateRequest->getUpdateable()->location->address_line_1;
            $resourceType = 'service location';
        } elseif ($updateRequest->getUpdateable() instanceof Organisation) {
            $resourceName = $updateRequest->getUpdateable()->name;
            $resourceType = 'organisation';
        } elseif ($updateRequest->getUpdateable() instanceof OrganisationEvent) {
            $resourceName = $updateRequest->getUpdateable()->title;
            $resourceType = 'organisation event';
        }

        // Send notification to the submitter.
        $updateRequest->user->sendEmail(
            new \App\Emails\UpdateRequestReceived\NotifySubmitterEmail(
                $updateRequest->user->email,
                [
                    'SUBMITTER_NAME' => $updateRequest->user->first_name,
                    'RESOURCE_NAME' => $resourceName,
                    'RESOURCE_TYPE' => $resourceType,
                ]
            )
        );

        // Send notification to the global admins.
        Notification::sendEmail(
            new \App\Emails\UpdateRequestReceived\NotifyGlobalAdminEmail(
                config('local.global_admin.email'),
                [
                    'RESOURCE_NAME' => $resourceName,
                    'RESOURCE_TYPE' => $resourceType,
                    'RESOURCE_ID' => $updateRequest->updateable_id,
                    'REQUEST_URL' => backend_uri("/update-requests/{$updateRequest->id}"),
                ]
            )
        );
    }

    /**
     * @throws \Exception
     */
    protected function sendCreatedNotificationsForNew(UpdateRequest $updateRequest)
    {
        if ($updateRequest->getUpdateable() instanceof OrganisationSignUpForm) {
            // Send notification to the submitter.
            Notification::sendEmail(
                new \App\Emails\OrganisationSignUpFormReceived\NotifySubmitterEmail(
                    Arr::get($updateRequest->data, 'user.email'),
                    [
                        'SUBMITTER_NAME' => Arr::get($updateRequest->data, 'user.first_name'),
                        'ORGANISATION_NAME' => Arr::get($updateRequest->data, 'organisation.name'),
                    ]
                )
            );

            // Send notification to the global admins.
            Notification::sendEmail(
                new \App\Emails\OrganisationSignUpFormReceived\NotifyGlobalAdminEmail(
                    config('local.global_admin.email'),
                    [
                        'ORGANISATION_NAME' => Arr::get($updateRequest->data, 'organisation.name'),
                        'REQUEST_URL' => backend_uri("/update-requests/{$updateRequest->id}"),
                    ]
                )
            );
        }
    }
}
