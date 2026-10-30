<?php

/*
 * Filing thresholds that are a matter of judgement rather than law, kept out of
 * the controller so they can be argued about without reading request handling.
 */
return [

    /*
     * How far apart a photo's own GPS and the pin the resident placed must be
     * before the form stops and asks which one they meant.
     *
     * A few metres is GPS noise. At 100 m the two answers are genuinely
     * different places and the resident is the only person who knows which one
     * the pothole is at. Above that the form stays silent, because being asked a
     * question you already answered is worse than being trusted.
     */
    'location_conflict_threshold_metres' => (int) env('LOCATION_CONFLICT_THRESHOLD_METRES', 100),

];
