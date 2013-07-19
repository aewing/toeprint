<?php
/**
 * Toeprint Framework v0.1a - ( http://toeprint.phenocode.com/ )
 * Copyright (C) 2013 Drew Ewing
 * Unless explicitly acquired and licensed from Licensor under another license, the contents of this file are subject
 * to the Reciprocal Public License ("RPL") Version 1.5, or subsequent versions as allowed by the RPL, and You may not
 * copy or use this file in either source code or executable form, except in compliance with the terms and conditions
 * of the RPL. All software distributed under the RPL is provided strictly on an "AS IS" basis, WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESS OR IMPLIED, AND LICENSOR HEREBY DISCLAIMS ALL SUCH WARRANTIES, INCLUDING WITHOUT LIMITATION,
 * ANY WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, QUIET ENJOYMENT, OR NON-INFRINGEMENT.
 * See the RPL for specific language governing rights and limitations under the RPL.
 * For more details on this particular license see http://toeprint.phenocode.com/license
 * For more information on the RPL license please see http://en.wikipedia.org/wiki/Reciprocal_Public_License
 */
    class toeprint_WidgetManager {
        private $widgets = array();
        public function __construct() {

        }
        public function register($widget, $method) {
            $this->widgets[$widget] = $method;
        }
        public function get($widget) {
            return $this->widgets[$widget];
        }
    }

    class toeprint_Widget extends toeprint_Template {
        public function __construct($name, $defaults) {

            $path = TOEPRINT_VIEW_PATH . '/widgets/' . $name . '/.phtml';

        }
    }