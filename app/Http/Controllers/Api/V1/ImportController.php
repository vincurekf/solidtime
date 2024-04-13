<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Import\ImportRequest;
use App\Models\Organization;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    /**
     * Import data into the organization
     *
     * @throws AuthorizationException
     *
     * @operationId importData
     */
    public function import(Organization $organization, ImportRequest $request, ImportService $importService): JsonResponse
    {
        $this->checkPermission($organization, 'import');

        try {
            $importData = base64_decode($request->input('data'), true);
            if ($importData === false) {
                return new JsonResponse([
                    'message' => 'Invalid base64 encoded data',
                ], 400);
            }

            $report = $importService->import(
                $organization,
                $request->input('type'),
                $importData
            );

            return new JsonResponse([
                /** @var array{
                 *   clients: array{
                 *     created: int,
                 *   },
                 *   projects: array{
                 *     created: int,
                 *   },
                 *   tasks: array{
                 *     created: int,
                 *   },
                 *   time-entries: array{
                 *     created: int,
                 *   },
                 *   tags: array{
                 *     created: int,
                 *   },
                 *   users: array{
                 *     created: int,
                 *   }
                 * } $report Import report */
                'report' => $report->toArray(),
            ], 200);
        } catch (ImportException $exception) {
            report($exception);

            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }
}
