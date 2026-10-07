<?php

test('instance endpoint returns the app name and version', function () {
    config()->set('opencal.version', '1.2.3');

    $this->getJson(route('api.instance'))
        ->assertOk()
        ->assertJson([
            'name' => config('app.name'),
            'version' => '1.2.3',
        ]);
});

test('instance endpoint is publicly accessible without auth', function () {
    $this->getJson(route('api.instance'))->assertOk();
});
