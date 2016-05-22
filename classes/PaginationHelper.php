<?php
/**
 * Eine Hilfsklasse zur Realisierung des Pagings.
 * Ein Objekt dieser Klasse enthält die folgenden Informationen
 * - den Startpunkt (offset) der anzuzeigenden Einträge,
 * - den Endpunkt (limit) der anzuzeigenden Einträge,
 * - die aktuelle Schrittweite (steps) der anzuzeigenden Einträge,
 * - einen Verweis auf die umliegenden Vorgänger (prev) unter Berücksichtigung der Schrittweite sowie
 * - einen Verweis auf die umliegenden Nachfolger (next) unter Berücksichtigung der Schrittweite.   
 */
class PaginationHelper {
    protected $start, $end, $next, $prev, $steps;
    protected static $DEFAULT_STEPS = 10;
    
    /**
     * Hilfsmethode, die einen PaginationHelper zurückgibt.
     *  
     * @param int $count Gesamtanzahl der Einträge.
     *  
     * @return PaginationHelper Ein Objekt, welches alle für das Paging relevanten Informationen trägt.
     */
    public static function getHelper($count) {
        // Erlaubt sind nur numerische Parameter
        if (!self::isRequestValid()) {
            http_response_code(400);
            die();
        }
        
        if (!isset($_GET['start'])) $start = NULL;
        else $start = $_GET['start'];

        // Schrittweite ermitteln wenn möglich, ansonsten Standardschrittweite von 10
        if (isset($_GET['end'])) {
            $end = $_GET['end'];
            $steps = $end - $start;
        } else {
            $steps = self::$DEFAULT_STEPS;
            if ($count < $start + $steps) $end = $count;
            else $end = $start + $steps;
        }
        
        // Angeforderter Bereich ist zu groß oder übersteigt die Anzahl der Einträge
        if ($steps > 1000 || max(-1, $start) >= $count || $end > $count) {
            http_response_code(416);
            die();
        }
        
        return new PaginationHelper($start, $end, $steps, $count); 
    }
    
    /**
     * Prüft ob die verwendeten Teile der Abfrage-Zeichenkette ausschließlich Ziffern enthält.
     */
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