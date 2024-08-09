<?php

namespace App\Providers;

use App\Contracts\VariableSubstituter;
use App\VariableSubstitution\DoubleParenthesisVariableSubstituter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use CarbonImmutable instead of Carbon.
        Date::use(CarbonImmutable::class);

        // Geocode.
        switch (config('geocode.geocode_driver')) {
            case 'google':
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\GoogleGeocoder::class);
                break;
            case 'nominatim':
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\NominatimGeocoder::class);
                break;
            case 'stub':
            default:
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\StubGeocoder::class);
                break;
        }

        // Email Sender.
        switch (config('mail.default')) {
            case 'gov':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\GovNotifyEmailSender::class);
                break;
            case 'mailgun':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\MailgunEmailSender::class);
                break;
            case 'null':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\NullEmailSender::class);
                break;
            case 'log':
            default:
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\LogEmailSender::class);
                break;
        }

        // SMS Sender.
        switch (config('sms.sms_driver')) {
            case 'gov':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\GovNotifySmsSender::class);
                break;
            case 'twilio':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\TwilioSmsSender::class);
                break;
            case 'null':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\NullSmsSender::class);
                break;
            case 'log':
            default:
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\LogSmsSender::class);
                break;
        }

        // Variable substitution.
        $this->app->bind(VariableSubstituter::class, DoubleParenthesisVariableSubstituter::class);

        /**
         * Flagged functionality.
         */
        Validator::extendImplicit('present_if_flagged', function ($attribute, $value, $parameters, $validator) {
            switch ($attribute) {
                case 'cqc_location_id':
                    $flagged = config('flags.cqc_location');
                    break;
                case 'tags':
                    $flagged = config('flags.service_tags');
                    break;
                case 'offerings':
                    $flagged = config('flags.offerings');
                    break;
                default:
                    $flagged = false;
                    break;
            }

            return $flagged ? Arr::has($validator->getData(), $attribute) : true;
        }, Lang::get('validation.present'));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
