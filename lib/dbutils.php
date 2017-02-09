<?php

function sql_in_clause($fieldName, $values) {
    if ( count($values) > 0 ) {
      $ph = implode(',', array_fill(0,count($values),'?'));
      return "$fieldName IN(" . $ph . ")";
    } else {
      return $fieldName;
    }
}

