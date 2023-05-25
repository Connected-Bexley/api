<?php

namespace App\Models\Mutators;

trait PageMutators
{
    /**
     * Specify parent id attribute mutator.
     *
     * @param mixed $value
     */
    public function setParentAttribute($value)
    {
        $this->setParentIdAttribute($value);
    }

    /**
     * Get the order attribute.
     *
     * @param type name
     * @return int
     */
    public function getOrderAttribute()
    {
        return $this->prevSiblings()->count();
    }

    /**
     * Specify content attribute mutator.
     *
     * @param mixed $value
     */
    public function setContentAttribute($value)
    {
        // Sanitize the JSON content field

        foreach ($value as $section => &$sectionContent) {
            if (!empty($sectionContent['content'])) {
                foreach ($sectionContent['content'] as &$content) {
                    if ($content['type'] === 'copy') {
                        $content['value'] = sanitize_markdown($content['value'] ?? '');
                    }
                }
            }
        }

        return $this->attributes['content'] = json_encode($value);
    }

    /**
     * Get the last ancestor with type 'landing page'.
     *
     * @return \App\Models\Page|null
     */
    public function getLandingPageAttribute()
    {
        return $this->landingPageAncestors->last();
    }
}
