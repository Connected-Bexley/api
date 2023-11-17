<?php

namespace App\Docs\Schemas\Setting;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class SettingSchema extends Schema
{
    public static function create(string $objectId = null): BaseObject
    {
        $global = Schema::object('global')->properties(
            Schema::string('footer_title'),
            Schema::string('footer_content')->format('markdown'),
            Schema::string('contact_phone'),
            Schema::string('contact_email'),
            Schema::string('facebook_handle'),
            Schema::string('twitter_handle')
        );

        $home = Schema::object('home')->properties(
            Schema::string('search_title'),
            Schema::string('categories_title'),
            Schema::string('personas_title'),
            Schema::string('personas_content')->format('markdown'),
            Schema::array('banners')
                ->items(
                    Schema::object()
                        ->properties(
                            Schema::string('title'),
                            Schema::string('content')->format('markdown'),
                            Schema::string('button_text'),
                            Schema::string('button_url')
                        )
                )
        );

        $termsAndConditions = Schema::object('terms_and_conditions')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown')
        );

        $privacyPolicy = Schema::object('privacy_policy')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown')
        );

        $accessibilityStatement = Schema::object('accessibility_statement')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown')
        );

        $about = Schema::object('about')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown'),
            Schema::string('video_url')->nullable()
        );

        $contact = Schema::object('contact')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown')
        );

        $getInvolved = Schema::object('get_involved')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown')
        );

        $favourites = Schema::object('favourites')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown')
        );

        $banner = Schema::object('banner')->properties(
            Schema::string('title'),
            Schema::string('content')->format('markdown'),
            Schema::string('button_text'),
            Schema::string('button_url'),
            Schema::boolean('has_image')
        );

        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::object('cms')
                    ->properties(
                        Schema::object('frontend')
                            ->properties(
                                $global,
                                $home,
                                $termsAndConditions,
                                $privacyPolicy,
                                $accessibilityStatement,
                                $about,
                                $contact,
                                $getInvolved,
                                $favourites,
                                $banner
                            )
                    )
            );
    }
}
