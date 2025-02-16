<?php

namespace JouwWeb\Sendcloud\Model;

use JouwWeb\Sendcloud\Utility;

class Parcel
{
    public const LABEL_FORMAT_A6 = 1;
    public const LABEL_FORMAT_A4_TOP_LEFT = 2;
    public const LABEL_FORMAT_A4_TOP_RIGHT = 3;
    public const LABEL_FORMAT_A4_BOTTOM_LEFT = 4;
    public const LABEL_FORMAT_A4_BOTTOM_RIGHT = 5;
    public const LABEL_FORMATS = [
        self::LABEL_FORMAT_A6,
        self::LABEL_FORMAT_A4_TOP_LEFT,
        self::LABEL_FORMAT_A4_TOP_RIGHT,
        self::LABEL_FORMAT_A4_BOTTOM_LEFT,
        self::LABEL_FORMAT_A4_BOTTOM_RIGHT,
    ];

    // Obtained from https://panel.sendcloud.sc/api/v2/parcels/statuses (with API auth)
    public const STATUS_ANNOUNCED = 1;
    public const STATUS_EN_ROUTE_TO_SORTING_CENTER = 3;
    public const STATUS_DELIVERY_DELAYED = 4;
    public const STATUS_SORTED = 5;
    public const STATUS_NOT_SORTED = 6;
    public const STATUS_BEING_SORTED = 7;
    public const STATUS_DELIVERY_ATTEMPT_FAILED = 8;
    public const STATUS_DELIVERED = 11;
    public const STATUS_AWAITING_CUSTOMER_PICKUP = 12;
    public const STATUS_ANNOUNCED_NOT_COLLECTED = 13;
    public const STATUS_ERROR_COLLECTING = 15;
    public const STATUS_SHIPMENT_PICKED_UP_BY_DRIVER = 22;
    public const STATUS_UNABLE_TO_DELIVER = 80;
    public const STATUS_PARCEL_EN_ROUTE = 91;
    public const STATUS_DRIVER_EN_ROUTE = 92;
    public const STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER = 93;
    public const STATUS_NO_LABEL = 999;
    public const STATUS_READY_TO_SEND = 1000;
    public const STATUS_BEING_ANNOUNCED = 1001;
    public const STATUS_ANNOUNCEMENT_FAILED = 1002;
    public const STATUS_UNKNOWN_STATUS = 1337;
    public const STATUS_CANCELLED_UPSTREAM = 1998;
    public const STATUS_CANCELLATION_REQUESTED = 1999;
    public const STATUS_CANCELLED = 2000;
    public const STATUS_SUBMITTING_CANCELLATION_REQUEST = 2001;
    public const STATUSES = [
        self::STATUS_ANNOUNCED,
        self::STATUS_EN_ROUTE_TO_SORTING_CENTER,
        self::STATUS_DELIVERY_DELAYED,
        self::STATUS_SORTED,
        self::STATUS_NOT_SORTED,
        self::STATUS_BEING_SORTED,
        self::STATUS_DELIVERY_ATTEMPT_FAILED,
        self::STATUS_DELIVERED,
        self::STATUS_AWAITING_CUSTOMER_PICKUP,
        self::STATUS_ANNOUNCED_NOT_COLLECTED,
        self::STATUS_ERROR_COLLECTING,
        self::STATUS_SHIPMENT_PICKED_UP_BY_DRIVER,
        self::STATUS_UNABLE_TO_DELIVER,
        self::STATUS_PARCEL_EN_ROUTE,
        self::STATUS_DRIVER_EN_ROUTE,
        self::STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER,
        self::STATUS_NO_LABEL,
        self::STATUS_READY_TO_SEND,
        self::STATUS_BEING_ANNOUNCED,
        self::STATUS_ANNOUNCEMENT_FAILED,
        self::STATUS_UNKNOWN_STATUS,
        self::STATUS_CANCELLED_UPSTREAM,
        self::STATUS_CANCELLATION_REQUESTED,
        self::STATUS_CANCELLED,
        self::STATUS_SUBMITTING_CANCELLATION_REQUEST,
    ];

    public const CUSTOMS_SHIPMENT_TYPE_GIFT = 0;
    public const CUSTOMS_SHIPMENT_TYPE_DOCUMENTS = 1;
    public const CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS = 2;
    public const CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_SAMPLE = 3;
    public const CUSTOMS_SHIPMENT_TYPE_RETURNED_GOODS = 4;
    public const CUSTOMS_SHIPMENT_TYPES = [
        self::CUSTOMS_SHIPMENT_TYPE_GIFT,
        self::CUSTOMS_SHIPMENT_TYPE_DOCUMENTS,
        self::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS,
        self::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_SAMPLE,
        self::CUSTOMS_SHIPMENT_TYPE_RETURNED_GOODS,
    ];

    /**
     * Constants for the 'errors' query parameter for parcel creation
     * @see https://api.sendcloud.dev/docs/sendcloud-public-api/parcels-and-error-handling#errorsverbose
     */
    public const ERROR_VERBOSE = 'verbose';
    public const ERROR_VERBOSE_CARRIER = 'verbose-carrier';
    public const ERRORS_VERBOSE = [
        self::ERROR_VERBOSE,
        self::ERROR_VERBOSE_CARRIER
    ];

    public static function fromData(array $data): self
    {
        $labelUrls = [];
        foreach (self::LABEL_FORMATS as $format) {
            $labelUrl = Utility::getLabelUrlFromData($data, $format);
            if ($labelUrl) {
                $labelUrls[$format] = $labelUrl;
            }
        }

        $items = [];
        if (isset($data['parcel_items'])) {
            foreach ((array)$data['parcel_items'] as $itemData) {
                $items[] = ParcelItem::fromData($itemData);
            }
        }

        $errors = [];
        if (isset($data['errors'])) {
            foreach ((array)$data['errors'] as $key => $itemData) {
                $errors[$key][] = $itemData;
            }
        }

        return new self(
            (int)$data['id'],
            (int)$data['status']['id'],
            (string)$data['status']['message'],
            new \DateTimeImmutable((string)$data['date_created']),
            (string)$data['tracking_number'],
            (int)round(((float)$data['weight']) * 1000),
            Address::fromParcelData($data),
            count($labelUrls) > 0 ? $labelUrls : null,
            isset($data['tracking_url']) ?  (string)$data['tracking_url'] : null,
            isset($data['carrier']['code']) ? (string)$data['carrier']['code'] : null,
            isset($data['order_number']) ? (string)$data['order_number']: null,
            isset($data['shipment']['id']) ? (int)$data['shipment']['id'] : null,
            isset($data['to_service_point']) ? (int)$data['to_service_point'] : null,
            isset($data['customs_invoice_nr']) ? (string)$data['customs_invoice_nr'] : null,
            isset($data['customs_shipment_type']) ? (int)$data['customs_shipment_type'] : null,
            $items,
            $errors,
        );
    }

    /**
     * @param string[]|null $labelUrls
     * @param ParcelItem[] $items
     * @param array<string, string> $errors
     */
    public function __construct(
        protected int $id,
        protected int $statusId,
        protected string $statusMessage,
        protected \DateTimeImmutable $created,
        protected string $trackingNumber,
        protected int $weight,
        protected Address $address,
        protected ?array $labelUrls = null,
        protected ?string $trackingUrl = null,
        protected ?string $carrier = null,
        protected ?string $orderNumber = null,
        protected ?int $shippingMethodId = null,
        protected ?int $servicePointId = null,
        protected ?string $customsInvoiceNumber = null,
        protected ?int $customsShipmentType = null,
        protected array $items = [],
        protected array $errors = [],
    ) {
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function getStatusMessage(): string
    {
        return $this->statusMessage;
    }

    public function getStatusId(): int
    {
        return $this->statusId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function hasLabel(): bool
    {
        return (bool)$this->labelUrls;
    }

    public function getLabelUrl(int $format): ?string
    {
        return $this->labelUrls[$format] ?? null;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getShippingMethodId(): ?int
    {
        return $this->shippingMethodId;
    }

    public function getServicePointId(): ?int
    {
        return $this->servicePointId;
    }

    public function getCustomsInvoiceNumber(): ?string
    {
        return $this->customsInvoiceNumber;
    }

    public function getCustomsShipmentType(): ?int
    {
        return $this->customsShipmentType;
    }

    /**
     * @return ParcelItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'address' => $this->getAddress()->toArray(),
            'carrier' => $this->getCarrier(),
            'created' => $this->getCreated()->format(\DateTimeInterface::ATOM),
            'id' => $this->getId(),
            'labels' => array_map(function (int $format): ?string {
                return $this->getLabelUrl($format);
            }, self::LABEL_FORMATS),
            'orderNumber' => $this->getOrderNumber(),
            'servicePointId' => $this->getServicePointId(),
            'shippingMethodId' => $this->getShippingMethodId(),
            'statusId' => $this->getStatusId(),
            'statusMessage' => $this->getStatusMessage(),
            'trackingNumber' => $this->getTrackingNumber(),
            'trackingUrl' => $this->getTrackingUrl(),
            'weight' => $this->getWeight(),
            'customsInvoiceNumber' => $this->getCustomsInvoiceNumber(),
            'customsShipmentType' => $this->getCustomsShipmentType(),
            'items' => array_map(function (ParcelItem $item): array {
                return $item->toArray();
            }, $this->getItems()),
        ];
    }

    public function __toString(): string
    {
        if ($this->getOrderNumber()) {
            $suffix = sprintf('for order %s', $this->getOrderNumber());
        } else {
            $suffix = sprintf('for %s', $this->getAddress());
        }

        return sprintf('parcel %s %s', $this->getId(), $suffix);
    }
}
