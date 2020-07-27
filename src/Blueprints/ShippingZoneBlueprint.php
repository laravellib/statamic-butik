<?php

namespace Jonassiewertsen\StatamicButik\Blueprints;

use Jonassiewertsen\StatamicButik\Http\Models\ShippingProfile;
use Jonassiewertsen\StatamicButik\Http\Models\ShippingZone;
use Statamic\Facades\Blueprint as StatamicBlueprint;
use Symfony\Component\Intl\Countries;

class ShippingZoneBlueprint extends Blueprint
{
    private array $shippingTypeNames;

    public function __invoke()
    {
        return StatamicBlueprint::make()->setContents([
            'sections' => [
                'main' => [
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field'  => [
                                'type'     => 'text',
                                'width'    => '50',
                                'display'  => __('butik::cp.name'),
                                'validate' => 'required',
                            ],
                        ],
                        [
                            'handle' => 'type',
                            'field'  => [
                                'type'     => 'select',
                                'width'    => '50',
                                'options'  => $this->shippingTypes(),
                                'display'  => __('butik::cp.type'),
                                'validate' => 'required',
                            ],
                        ],
                        [
                            'handle' => 'shipping_profile_slug',
                            'field'  => [
                                'type'     => 'hidden',
                                'validate' => 'required|exists:butik_shipping_profiles,slug',
                            ],
                        ],
                        [
                            'handle' => 'countries',
                            'field' => [
                                'type' => 'select',
                                'options' => Countries::getNames(app()->getLocale()),
                                'clearable' => false,
                                'multiple' => true,
                                'searchable' => true,
                                'taggable' => false,
                                'push_tags' => false,
                                'cast_booleans' => false,
                                'localizable' => false,
                                'listable' => true,
                                'display' => 'Countries',
                                'validate' => ['required', 'array',
                                    function ($attribute, $value, $fail) {
                                        foreach ($value as $country_code) {
                                            if (! Countries::exists($country_code)) {
                                                $fail('Invalid country code: ' . $country_code);
                                            }
                                        }
                                    },
                                    function ($attribute, $value, $fail) {
                                        if(ShippingZone::all()
                                            ->filter(function($shipping_zone) use ($value) {
                                                foreach ($value as $country_code) {
                                                    if ($shipping_zone->countries->contains($country_code)) {
                                                        return true;
                                                    }
                                                }
                                            })
                                            ->count() > 1) {
                                            $fail('One of the countries is already added to another shipping zone.');
                                        }
                                    }
                                ]
                            ]
                        ]
                    ],
                ],
            ],
        ]);
    }

    /**
     * In case the Product will be edited, the slug will be read only
     */
    private function slugReadOnly(): bool
    {
        return $this->isRoute('statamic.cp.butik.shipping-zones.edit');
    }

    private function shippingzonesUniqueRule()
    {
        return $this->ignoreUnqiueOn(
            'butik_shipping_zones',
            'slug',
            'statamic.cp.butik.shipping-zones.update'
        );
    }

    private function fetchShippingProfiles(): array
    {
        return ShippingProfile::pluck('title', 'slug')->toArray();
    }

    private function shippingTypes(): array
    {
        $types                   = config('butik.shipping');
        $this->shippingTypeNames = [];

        foreach ($types as $slug => $shippingType) {
            $name = (new $shippingType())->name;

            $this->shippingTypeNames[$slug] = $name;
        }

        return $this->shippingTypeNames;
    }
}
