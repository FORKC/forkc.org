<?php

class Cornerstone_Stable_Sort {
  public function __construct( $cmp ) {
    $this->user_cmp = $cmp;
  }

  protected function cmp($a, $b) {
    $user = call_user_func( $this->user_cmp, $a[1], $b[1]);
    return $user ?: ($a[0] - $b[0]);
  }

  protected function index_array( $el ) {
    return array( $this->i++, $el );
  }

  public function sort( $array ) {
    $this->i = 0;
    $indexed = array_map( array( $this, 'index_array' ), $array );
    usort($indexed, array( $this, 'cmp'));
    $indexed = array_column($indexed, 1);
    return $indexed;
  }
}
