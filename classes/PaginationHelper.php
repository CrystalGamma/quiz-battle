<?php
class PaginationHelper {
    protected $start, $end, $next, $prev, $steps;
    protected static $DEFAULT_STEPS = 10;
    
    public static function getHelper($count) {
        if (!self::isRequestValid()) {
            http_response_code(400);
            die();
        }
        
        if (max(-1, $_GET['start']) >= $count || $_GET['end'] > $count) {
            http_response_code(404);
            die();
        }
        
        return new PaginationHelper($_GET['start'], $_GET['end'], $count); 
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
    
    private function __construct($start, $end, $count) {
        if (is_null($start))
            $start = 0;
        
        if (is_null($end)) {
            $steps = self::$DEFAULT_STEPS;
            if ($count < $start + $steps) $end = $count;
            else $end = $start + $steps;
        } else
            $steps = $end - $start;
        
        $next = $end >= $count ? null : $end;
        if (!is_null($next)) {
            if ($steps != self::$DEFAULT_STEPS)
                $next .= "&end=".min($count, ($next + $steps));
            $next = "?start=$next";
        }

        $prev = $_GET['start'] == 0 ? null : max(0, $_GET['start'] - $steps);
        if (!is_null($prev)) {
            if ($steps != self::$DEFAULT_STEPS)
                $prev .= "&end=".($prev + $steps);
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