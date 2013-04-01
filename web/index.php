<?php

/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
 *
 * To get the full copyright and license informations, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */

/**
 * Index.php
 *
 * @author MArc Schmidt <marc@kryn.org>
 */

//load main config, setup some constants and check some requirements.
require(__DIR__ . '/../core/bootstrap.php');

Core\Kryn::checkStaticCaching();

//attach error handler, init propel, load module configs, initialise main controllers and setup the autoloader.
require(__DIR__ . '/../core/bootstrap.startup.php');

Core\Kryn::getLogger()->addDebug('Bootstrap loaded.');

//Setup the HTTPKernel.
Core\Kryn::setupHttpKernel();

//Handle the request.
Core\Kryn::handleRequest();
