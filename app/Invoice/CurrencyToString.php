<?php
namespace Woo_BG\Invoice;

class CurrencyToString {
	public static function number_to_text( $number, $stotinki = false ) {
        $_num0 = array(0 => "нула",1 => "един",2 => "две",3 => "три",4 => "четири", 5 => "пет",6 => "шест",7 => "седем",8 => "осем",9 => "девет", 10 => "десет", 11 => "единадесет", 12 => "дванадесет"); 
        $_num100 = array(1 => "сто", 2 => "двеста", 3 => "триста");

        $number = (int)$number;

        $_div10 = ( $number - $number % 10 ) / 10;
        $_mod10 = $number % 10;
        $_div100 = ( $number - $number % 100 ) / 100;
        $_mod100 = $number % 100;
        $_div1000 = ( $number - $number % 1000 ) / 1000;
        $_mod1000 = $number % 1000;
        $_div1000000 = ( $number - $number % 1000000 ) / 1000000;
        $_mod1000000 = $number % 1000000;
        $_div1000000000 = ( $number - $number % 1000000000 ) / 1000000000;
        $_mod1000000000 = $number % 1000000000;

        if ( $number == 0 ) {
            return $_num0[$number];
        }
        /* До двайсет */
        if ( $number > 0 && $number < 20 ) {
            if ($stotinki && $number == 1)
                return "една";
            if ($stotinki && $number == 2)
                return "две";
            if ( $number == 2)
                return "два";
            return isset( $_num0[$number]) ? $_num0[$number] : $_num0[$_mod10]."надесет";
        }
        /* До сто */
        if ( $number > 19 && $number < 100 ) {
            $tmp = ( $_div10 == 2) ? "двадесет" : $_num0[$_div10]."десет";
            $tmp = $_mod10 ? $tmp." и ". self::number_to_text( $_mod10,$stotinki) : $tmp;
            return $tmp;
        }
        /* До хиляда */
        if ( $number > 99 && $number < 1000 ) {
            $tmp = isset( $_num100[$_div100]) ? $_num100[$_div100] : $_num0[$_div100]."стотин";
            if (( $_mod100 % 10 == 0 || $_mod100 < 20 ) && $_mod100 != 0 ) {
                $tmp .= " и";
            }
            if ( $_mod100 ) {
                $tmp .= " ". self::number_to_text( $_mod100 );
            }
            return $tmp;
        }
        /* До милион */
        if ( $number > 999 && $number < 1000000 ) {
            /* Damn bulgarian @#$%@#$% два хиляди is wrong :) */
            $tmp = ( $_div1000 == 1) ? "хиляда" :
                   (( $_div1000 == 2) ? "две хиляди" :  self::number_to_text( $_div1000 )." хиляди");
            $_num0[2] = "два";
            if (( $_mod1000 % 10 == 0 || $_mod1000 < 20 ) && $_mod1000 != 0 ) {
                if (!(( $_mod100 % 10 == 0 || $_mod100 < 20 ) && $_mod100 != 0 )) {
                    $tmp .= " и";
                }
            }
            if (( $_mod1000 % 10 == 0 || $_mod1000 < 20 ) && $_mod1000 != 0 && $_mod1000 < 100 ) {
                $tmp .= " и";
            }
            if ( $_mod1000 ) {
                $tmp .= " ". self::number_to_text( $_mod1000 );
            }
            return $tmp;
        }
        /* Над милион */
        if ( $number > 999999 && $number < 1000000000 ) {
            $tmp = ( $_div1000000 == 1) ? "един милион" :  self::number_to_text( $_div1000000 )." милиона";
            if (( $_mod1000000 % 10 == 0 || $_mod1000000 < 20 ) && $_mod1000000 != 0 ) {
                if (!(( $_mod1000 % 10 == 0 || $_mod1000 < 20 ) && $_mod1000 != 0 )) {
                    if (!(( $_mod100 % 10 == 0 || $_mod100 < 20 ) && $_mod100 != 0 )) {
                        $tmp .= " и";
                    }
                }
            }
            $and = ", ";
            if (( $_mod1000000 % 10 == 0 || $_mod1000000 < 20 ) && $_mod1000000 != 0 && $_mod1000000 < 1000 ) {
                if (( $_mod1000 % 10 == 0 || $_mod1000 < 20 ) && $_mod1000 != 0 && $_mod1000 < 100 ) {
                    $tmp .= " и";
                }
            }
            if ( $_mod1000000 ) {
                $tmp .= " ". self::number_to_text( $_mod1000000 );
            }
            return $tmp;
        }
        /* Над милиард */
        if ( $number > 99999999 && $number <= 2000000000 ) {
            $tmp = ( $_div1000000000 == 1) ? "един милиард" : "";
            $tmp = ( $_div1000000000 == 2) ? "два милиарда" : $tmp;
            if ( $_mod1000000000 ) {
                $tmp .= " ". self::number_to_text( $_mod1000000000 );
            }
            return $tmp;
        }
        /* Bye ... */
        return "";
    }

    public static function number_to_lev( $number ) {
        list($lv, $st) = explode(".", number_format( $number, 2, ".", "" ) );
        $lv = (int)$lv;
        if ($lv >= 2000000000 )
            return "Твърде голямо число";
        $text  =  self::number_to_text($lv);
        $text .= $lv == 1 ? " лев":" лева";
        if ($st <> 0 )
            $text = preg_replace( "/^един /","",$text );
        if ($st && $st != 0 ) {
            $sttext =  self::number_to_text($st,true);
            $text .= " и ". self::number_to_text($st,true);
            $text .= $st == 1 ? " стотинка":" стотинки";
        }
        return $text;
    }
}