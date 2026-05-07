<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\V1;

use App\Application\Support\SpotSymbolNormalizer;
use App\Domain\Crypto\Contracts\MonitoredCoinRepository;
use App\Domain\Crypto\Entities\MonitoredCoin;
use App\Http\Controllers\Controller;
use App\Presentation\Http\Requests\Api\V1\StoreMonitoredCoinRequest;
use App\Presentation\Http\Requests\Api\V1\UpdateMonitoredCoinRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Monitored coins', description: 'CRUD sobre pares spot monitorados')]
final class MonitoredCoinController extends Controller
{
    public function __construct(
        private readonly MonitoredCoinRepository $coins,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/monitored-coins',
        summary: 'List monitored pairs',
        tags: ['Monitored coins'],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function index(): JsonResponse
    {
        $rows = array_map(static fn (MonitoredCoin $e): array => self::serialize($e), $this->coins->active());

        return response()->json(['data' => $rows]);
    }

    #[OA\Post(
        path: '/api/v1/monitored-coins',
        summary: 'Create monitored pair',
        tags: ['Monitored coins'],
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreMonitoredCoinRequest $request): JsonResponse
    {
        $quoteUpper = strtoupper((string) $request->string('quote_asset', 'USDT'));

        [$base, $quoteMatched] = SpotSymbolNormalizer::explode((string) $request->validated('symbol'), $quoteUpper);

        $entity = $this->coins->create(
            symbol: $base.$quoteMatched,
            baseAsset: $base,
            quoteAsset: $quoteMatched,
            label: $request->validated('label'),
            active: $request->boolean('active', true),
        );

        return response()->json(['data' => self::serialize($entity)], JsonResponse::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/v1/monitored-coins/{monitored_coin}',
        summary: 'Get monitored coin',
        tags: ['Monitored coins'],
        parameters: [new OA\Parameter(name: 'monitored_coin', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not Found')]
    )]
    public function show(int $monitored_coin): JsonResponse
    {
        $entity = $this->coins->findById($monitored_coin);
        if ($entity === null) {
            abort(404);
        }

        return response()->json(['data' => self::serialize($entity)]);
    }

    #[OA\Patch(
        path: '/api/v1/monitored-coins/{monitored_coin}',
        summary: 'Update monitored coin',
        tags: ['Monitored coins'],
        parameters: [new OA\Parameter(name: 'monitored_coin', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not Found')]
    )]
    public function update(UpdateMonitoredCoinRequest $request, int $monitored_coin): JsonResponse
    {
        if ($this->coins->findById($monitored_coin) === null) {
            abort(404);
        }

        $labelArgument = null;
        if ($request->has('label')) {
            $labelArgument = $request->input('label');
        }

        $activeArgument = null;
        if ($request->has('active')) {
            $activeArgument = $request->boolean('active');
        }

        $entity = $this->coins->update($monitored_coin, $labelArgument, $activeArgument);

        return response()->json(['data' => self::serialize($entity)]);
    }

    #[OA\Delete(
        path: '/api/v1/monitored-coins/{monitored_coin}',
        summary: 'Delete monitored coin',
        tags: ['Monitored coins'],
        parameters: [new OA\Parameter(name: 'monitored_coin', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not Found'),
        ]
    )]
    public function destroy(int $monitored_coin): JsonResponse
    {
        $existing = $this->coins->findById($monitored_coin);
        if ($existing === null) {
            abort(404);
        }

        $this->coins->delete($monitored_coin);

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private static function serialize(MonitoredCoin $entity): array
    {
        return [
            'id' => $entity->id,
            'uuid' => $entity->uuid,
            'symbol' => $entity->symbol,
            'base_asset' => $entity->baseAsset,
            'quote_asset' => $entity->quoteAsset,
            'label' => $entity->label,
            'active' => $entity->active,
        ];
    }
}
