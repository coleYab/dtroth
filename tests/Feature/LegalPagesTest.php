<?php

test('terms of service page renders', function () {
    $response = $this->get(route('legal.terms'));

    $response->assertOk();
});

test('privacy policy page renders', function () {
    $response = $this->get(route('legal.privacy-policy'));

    $response->assertOk();
});
