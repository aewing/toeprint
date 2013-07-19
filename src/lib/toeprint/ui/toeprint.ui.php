<?php
    class tpui {
        static function form($name, $action, $fields, $method='POST') {
            foreach($fields as $name => $field) {
                $fields['html'] = tpui::field($name, $field);
            }
            return tpui::fetchTemplate('form/form', array('name' => $name, 'action' => $action, 'method' => $method, 'fields' => $fields))->render();
        }
        static function field($name, $field) {
            // Sanitize field parameters
            if(!is_array($field) || !isset($field['name']) || !isset($field['label']))
                throw new Exception("Invalid field configuration for '" . $name . "'");
            if(!isset($field['type']) || !is_string($field['type'])) $field['type'] = 'text';
            if(!isset($field['value'])) $field['value'] = false;
            // Attempt to hook based on field type
            $hookResult = tp::hook('tpui-field-' . $field['type']);
            $template = tpui::fetchTemplate('form/fields/' . $field['type'], array('field' => $field));
            return $template->render();
        }
        static function fetchTemplate($name, $assign=array(), $cache=true) {
            if($cache) {
                $cached = tp::universal('tpui-template-' . tp::slug($name));
                $cached->reset($assign);
                return $cached;
            }
            $params = array('name' => $name);
            $path = tp::hook('tpui::fetchTemplate', $params, TOEPRINT_LIB_PATH . '/toeprint/ui/views/' . $name . '.phtml');
            $template = tp::template($path, $assign);
            if($cache) tp::universal('templates', array($name => $template));
            return $template;
        }
    }