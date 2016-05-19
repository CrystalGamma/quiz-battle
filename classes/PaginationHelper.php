<?php
class PaginationHelper {
    protected $start, $end, $next, $prev, $steps;
    protected static $DEFAULT_STEPS = 10;
    
    public static function getHelper($count) {
        if (!self::isRequestValid()) {
            http_response_code(400);
            die();
        }
        
        if (!isset($_GET['start'])) $start = NULL;
        else $start = $_GET['start'];
        
        if (!isset($_GET['end'])) {
            $steps = self::$DEFAULT_STEPS;
            if ($count < $start + $steps) $end = $count;
            else $end = $start + $steps;
        } else {
            $end = $_GET['end'];
            $steps = $end - $start;
        }
        
        if ($steps > 1000 || max(-1, $start) >= $count || $end > $count) {
            http_response_code(416);
            die();
        }
        
        return new PaginationHelper($start, $end, $steps, $count); 
    }
    
    public static function isRequestValid() {
        if (isset($_GET['start']))
            if (!is_numeric($_GET['start']))
                return false;
        
        if (isset($_GET['end']))
            if (!is_numeric($_GET['end']))
                return false;
        
        return true;
    }
    
    private function __construct($start, $end, $steps, $count) {
        $next = $end >= $count ? null : $end;
        if (!is_null($next)) {
            if ($steps != self::$DEFAULT_STEPS)
                $next .= '&end='.max($count, ($next + $steps));
            $next = "?start=$next";
        }

        $prev = $start == 0 ? null : max(0, $start - $steps);
        if (!is_null($prev)) {
            if ($steps != self::$DEFAULT_STEPS)
                $prev .= '&end='.($prev + $steps);
            $prev = "?start=$prev";
        }
        
        $this->start = $start;
        $this->end = $end;
        $this->next = $next;
        $this->prev = $prev;
        $this->steps = $steps;
    }
    
    public function getStart() {
        return (int) $this->start;
    }
    
    public function getEnd() {
        return (int) $this->end;
    }
    
    public function getNext() {
        return $this->next;
    }
    
    public function getPrevious() {
        return $this->prev;
    }
    
    public function getSteps() {
        return (int) $this->steps;
    }
}
?>