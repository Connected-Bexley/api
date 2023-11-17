<?php

namespace App\Models\Mutators;

trait ReferralMutators
{
    public function getNameAttribute(string $name): string
    {
        return decrypt($name);
    }

    public function setNameAttribute(string $name)
    {
        $this->attributes['name'] = encrypt($name);
    }

    public function getEmailAttribute(?string $email): ?string
    {
        return $email ? decrypt($email) : null;
    }

    public function setEmailAttribute(?string $email)
    {
        $this->attributes['email'] = $email ? encrypt($email) : null;
    }

    public function getPhoneAttribute(?string $phone): ?string
    {
        return $phone ? decrypt($phone) : null;
    }

    public function setPhoneAttribute(?string $phone)
    {
        $this->attributes['phone'] = $phone ? encrypt($phone) : null;
    }

    public function getOtherContactAttribute(?string $otherContact): ?string
    {
        return $otherContact ? decrypt($otherContact) : null;
    }

    public function setOtherContactAttribute(?string $otherContact)
    {
        $this->attributes['other_contact'] = $otherContact ? encrypt($otherContact) : null;
    }

    public function getPostcodeOutwardCodeAttribute(?string $postcodeOutwardCode): ?string
    {
        return $postcodeOutwardCode ? decrypt($postcodeOutwardCode) : null;
    }

    public function setPostcodeOutwardCodeAttribute(?string $postcodeOutwardCode)
    {
        $this->attributes['postcode_outward_code'] = $postcodeOutwardCode ? encrypt($postcodeOutwardCode) : null;
    }

    public function getCommentsAttribute(?string $comments): ?string
    {
        return $comments ? decrypt($comments) : null;
    }

    public function setCommentsAttribute(?string $comments)
    {
        $this->attributes['comments'] = $comments ? encrypt($comments) : null;
    }

    public function getRefereeNameAttribute(?string $refereeName): ?string
    {
        return $refereeName ? decrypt($refereeName) : null;
    }

    public function setRefereeNameAttribute(?string $refereeName)
    {
        $this->attributes['referee_name'] = $refereeName ? encrypt($refereeName) : null;
    }

    public function getRefereeEmailAttribute(?string $refereeEmail): ?string
    {
        return $refereeEmail ? decrypt($refereeEmail) : $refereeEmail;
    }

    public function setRefereeEmailAttribute(?string $refereeEmail)
    {
        $this->attributes['referee_email'] = $refereeEmail ? encrypt($refereeEmail) : null;
    }

    public function getRefereePhoneAttribute(?string $refereePhone): ?string
    {
        return $refereePhone ? decrypt($refereePhone) : null;
    }

    public function setRefereePhoneAttribute(?string $refereePhone)
    {
        $this->attributes['referee_phone'] = $refereePhone ? encrypt($refereePhone) : null;
    }
}
