<?php

namespace Parse\Test;

use Parse\ParseConfig;

class ConfigMock extends ParseConfig
{
    public function __construct()
    {
        parent::__construct();
        $this->setConfig([
            'foo'       => 'bar',
            'some'      => 1,
            'another'   => '<html>value</html>'
        ]);
    }
}
