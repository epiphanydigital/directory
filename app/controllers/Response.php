<?php

class generateOutput {

    public $data = null;
    public $callback = null;
    public $msg = array(
        'err' => "Something went wrong.",
        'null' => "No data returned."
        );

    public function message($type) {
        $data = array('system'=>array(
            'type' => $type,
            'message' => $this->msg[$type],
            ));
        return json_encode($data);
    }

    public function jsonOut() {
        if ($this->data==null) {
            $data = $this->message('null');
        } else {
            if (gettype($this->data)=='object') {
                $data = $this->data->toJson();
            } else {
                $data = json_encode($this->data);
            }
        }
        if (!empty($this->callback)) {
            header('Content-Type: application/javascript');
            return "{$this->callback}(" . $data . ");";
        } else {
            header('Content-type: text/javascript');
            return $data;
        }
    }
}