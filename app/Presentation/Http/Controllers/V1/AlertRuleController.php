<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\V1;

use App\Domain\Crypto\Contracts\AlertRuleRepository;
use App\Domain\Crypto\Entities\AlertRule as AlertRuleEntity;
use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Presentation\Http\Requests\Api\V1\StoreAlertRuleRequest;
use App\Presentation\Http\Requests\Api\V1\UpdateAlertRuleRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Alert rules', description: 'Parâmetros de gatilho e canais')]
final class AlertRuleController extends Controller
{
    public function __construct(
        private readonly AlertRuleRepository $rules,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/alert-rules',
        summary: 'List alert rules',
        tags: ['Alert rules'],
        parameters: [
            new OA\Parameter(
                name: 'monitored_coin_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function index(Request $request): JsonResponse
    {
        $coinFilter = filter_var($request->query('monitored_coin_id'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        $userId = $request->user()->id;

        $rows = array_map(
            static fn (AlertRuleEntity $rule): array => self::serialize($rule),
            $this->rules->list($userId, $coinFilter ?? null),
        );

        return response()->json(['data' => $rows]);
    }

    #[OA\Post(
        path: '/api/v1/alert-rules',
        summary: 'Create alert rule',
        tags: ['Alert rules'],
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreAlertRuleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $triggerRaw = $validated['trigger_type'];
        /** @phpstan-ignore-next-line */
        $trigger = $triggerRaw instanceof AlertTriggerType
            ? $triggerRaw
            : AlertTriggerType::from((string) $triggerRaw);

        $channelRaw = $validated['notify_channel'];
        /** @phpstan-ignore-next-line */
        $channel = $channelRaw instanceof NotificationChannel
            ? $channelRaw
            : NotificationChannel::from((string) $channelRaw);

        try {
            $entity = $this->rules->create(
                user_id: $request->user()->id,
                monitoredCoinId: $validated['monitored_coin_id'],
                triggerType: $trigger,
                thresholdPrice: $validated['threshold_price'] ?? null,
                thresholdPercent: $validated['threshold_percent'] ?? null,
                windowMinutes: $validated['window_minutes'] ?? null,
                channel: $channel,
                payload: $validated['notify_payload'] ?? [],
                active: (bool) ($validated['active'] ?? true),
            );
        } catch (DomainException $e) {
            abort(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }

        return response()->json(['data' => self::serialize($entity)], JsonResponse::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/v1/alert-rules/{alert_rule}',
        summary: 'Get alert rule',
        tags: ['Alert rules'],
        parameters: [
            new OA\Parameter(name: 'alert_rule', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'Not Found'),
        ]
    )]
    public function show(Request $request, int $alert_rule): JsonResponse
    {
        $userId = $request->user()->id;
        $rule = $this->rules->find($userId, $alert_rule);

        if ($rule === null) {
            abort(JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => self::serialize($rule)]);
    }

    #[OA\Patch(
        path: '/api/v1/alert-rules/{alert_rule}',
        summary: 'Patch alert rule',
        tags: ['Alert rules'],
        parameters: [
            new OA\Parameter(name: 'alert_rule', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation'),
            new OA\Response(response: 404, description: 'Not Found'),
        ]
    )]
    public function update(UpdateAlertRuleRequest $request, int $alert_rule): JsonResponse
    {
        $userId = $request->user()->id;
        $existing = $this->rules->find($userId, $alert_rule);

        if ($existing === null) {
            abort(JsonResponse::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();

        $triggerMutation = null;
        if (array_key_exists('trigger_type', $validated)) {
            $incoming = $validated['trigger_type'];

            /** @phpstan-ignore-next-line */
            $triggerMutation = $incoming instanceof AlertTriggerType
                ? $incoming
                : AlertTriggerType::from((string) $incoming);
        }

        $channelMutation = null;

        if (array_key_exists('notify_channel', $validated)) {

            /** @phpstan-ignore-next-line */
            $incomingChannel = $validated['notify_channel'];

            /** @phpstan-ignore-next-line */
            $channelMutation = $incomingChannel instanceof NotificationChannel
                ? $incomingChannel
                : NotificationChannel::from((string) $incomingChannel);

        }

        $thresholdPrice = array_key_exists('threshold_price', $validated)
            ? (is_string($validated['threshold_price']) ? $validated['threshold_price'] : null)
            : null;

        $thresholdPercent = array_key_exists('threshold_percent', $validated)
            ? (is_string($validated['threshold_percent']) ? $validated['threshold_percent'] : null)
            : null;

        $notifyPayload = null;

        if (array_key_exists('notify_payload', $validated) && is_array($validated['notify_payload'])) {
            $notifyPayload = $validated['notify_payload'];
        }

        $active = array_key_exists('active', $validated)
            ? (bool) $validated['active']
            : null;

        $windowArg = null;

        if (array_key_exists('window_minutes', $validated)) {
            $windowRaw = $validated['window_minutes'];
            $windowArg = $windowRaw === null ? null : (int) $windowRaw;
        }

        try {
            $entity = $this->rules->update(
                userId: $userId,
                id: $alert_rule,
                triggerType: $triggerMutation,
                thresholdPrice: array_key_exists('threshold_price', $validated) ? $thresholdPrice : null,
                thresholdPercent: array_key_exists('threshold_percent', $validated) ? $thresholdPercent : null,
                windowMinutes: $windowArg,
                channel: $channelMutation,
                payload: $notifyPayload !== null ? $notifyPayload : null,
                active: $active,
            );

            return response()->json(['data' => self::serialize($entity)]);
        } catch (DomainException $e) {
            abort(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }
    }

    #[OA\Delete(
        path: '/api/v1/alert-rules/{alert_rule}',
        summary: 'Delete alert rule',
        tags: ['Alert rules'],
        parameters: [
            new OA\Parameter(name: 'alert_rule', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not Found'),
        ]
    )]
    public function destroy(Request $request, int $alert_rule): JsonResponse
    {
        $userId = $request->user()->id;

        if ($this->rules->find($userId, $alert_rule) === null) {
            abort(JsonResponse::HTTP_NOT_FOUND);
        }

        $this->rules->delete($userId, $alert_rule);

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private static function serialize(AlertRuleEntity $rule): array
    {
        return [
            'id' => $rule->id,
            'uuid' => $rule->uuid,
            'monitored_coin_id' => $rule->monitoredCoinId,
            'trigger_type' => $rule->triggerType->value,
            'threshold_price' => $rule->thresholdPrice?->amountDecimal(),
            'threshold_percent' => $rule->thresholdPercent?->valueSigned(),
            'window_minutes' => $rule->windowMinutes,
            'notify_channel' => $rule->notifyChannel->value,
            'notify_payload' => $rule->notifyPayload,
            'active' => $rule->active,
        ];
    }
}
