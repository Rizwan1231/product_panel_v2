<?php

class db
{

  var $EwyUmraOWUEjWxRuCMIYOHOFJEmhliEoniSHE = 0;

  protected $CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws;

  var $fgjcPuIlYkGGfOUxmDEHItMGlDXWnUmskI;

  protected $FYvlAHHdLXUGtkRrsOAalaGTtgisccZxzU;

  protected $MeNnorFhvxXbSoBwbDXxNpFeBHWUWmdEEWt;

  protected $GcJEwrCMpdjACnrWOHsgQnWiCwPiqrXqk;

  protected $CScjoGyVZSPaSYnCEHwOmuxjelKuBQgQPNCsw;

  protected $scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM;

  function __construct ( $FYvlAHHdLXUGtkRrsOAalaGTtgisccZxzU, $MeNnorFhvxXbSoBwbDXxNpFeBHWUWmdEEWt, $GcJEwrCMpdjACnrWOHsgQnWiCwPiqrXqk, $CScjoGyVZSPaSYnCEHwOmuxjelKuBQgQPNCsw )
  {
    $this->FYvlAHHdLXUGtkRrsOAalaGTtgisccZxzU = $FYvlAHHdLXUGtkRrsOAalaGTtgisccZxzU;
    $this->MeNnorFhvxXbSoBwbDXxNpFeBHWUWmdEEWt = $MeNnorFhvxXbSoBwbDXxNpFeBHWUWmdEEWt;
    $this->GcJEwrCMpdjACnrWOHsgQnWiCwPiqrXqk = $GcJEwrCMpdjACnrWOHsgQnWiCwPiqrXqk;
    $this->CScjoGyVZSPaSYnCEHwOmuxjelKuBQgQPNCsw = $CScjoGyVZSPaSYnCEHwOmuxjelKuBQgQPNCsw;
    $this->FkgluNHEmpslCRhVcIPUGSxRTWpRIPRuGrP( );
  }

  function __destruct ( )
  {
    mysqli_close( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM );
    return true;
  }

  function FkgluNHEmpslCRhVcIPUGSxRTWpRIPRuGrP ( )
  {
    $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM = mysqli_connect( $this->CScjoGyVZSPaSYnCEHwOmuxjelKuBQgQPNCsw, $this->FYvlAHHdLXUGtkRrsOAalaGTtgisccZxzU, $this->MeNnorFhvxXbSoBwbDXxNpFeBHWUWmdEEWt, $this->GcJEwrCMpdjACnrWOHsgQnWiCwPiqrXqk );
    $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM->set_charset('utf8mb4');
    if ( mysqli_connect_errno( ) )
    {
      die( "Can Not Contect to database. Please check your config details." );
    }
    return true;
  }

  function query ( $query )
  {
    if ( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM )
    {
      $uwKPQcQIbZqWPJINqWjycBcsXDFsEZdMoM = func_num_args( );
      $vejaLSNVdOixjNJOLDRSppIoUwOYoxPtokrA = func_get_args( );
      $ERGELweuWItIbbYBTydvNpnSgMuSXyAuqI = array();
      for ( $PkmhXPbPvCcSikBmDzjHuyljGIDgckcLrskII = 1; $PkmhXPbPvCcSikBmDzjHuyljGIDgckcLrskII < $uwKPQcQIbZqWPJINqWjycBcsXDFsEZdMoM; $PkmhXPbPvCcSikBmDzjHuyljGIDgckcLrskII++ )
      {
        $ERGELweuWItIbbYBTydvNpnSgMuSXyAuqI[ ] = mysqli_real_escape_string( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM, $vejaLSNVdOixjNJOLDRSppIoUwOYoxPtokrA[ $PkmhXPbPvCcSikBmDzjHuyljGIDgckcLrskII ] );
      }
      $query = vsprintf( $query, $ERGELweuWItIbbYBTydvNpnSgMuSXyAuqI );
      $this->fgjcPuIlYkGGfOUxmDEHItMGlDXWnUmskI = $query;
      $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws = mysqli_query( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM, $query );
      $this->EwyUmraOWUEjWxRuCMIYOHOFJEmhliEoniSHE++;
    }
  }

  function simplequery ( $query )
  {
    if ( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM )
    {
      return mysqli_query( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM, $query );
    }
  }


  function getall ( )
  {
    if ( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM && $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws )
    {
      $DqxWVhFTLNAKTbhfsRUoDwEXckDTaziPWl = array();
      if ( $this->num_rows( ) > 0 )
      {
        while ( $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs = mysqli_fetch_array( $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws, MYSQLI_ASSOC ) )
        {
          $DqxWVhFTLNAKTbhfsRUoDwEXckDTaziPWl[ ] = $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs;
        }
      }
      return $DqxWVhFTLNAKTbhfsRUoDwEXckDTaziPWl;
    }
    return false;
  }

  public function getdata ( )
  {
    if ( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM && $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws )
    {
      $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs = array();
      if ( $this->num_rows( ) > 0 )
      {
        $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs = mysqli_fetch_array( $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws, MYSQLI_ASSOC );
      }
      return $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs;
    }
    return false;
  }

  public function iycjdDvSoqgLkyJNknsfSqnsRpysAuGjvCAJk ( )
  {
    if ( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM && $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws )
    {
      $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs = false;
      if ( $this->num_rows( ) > 0 )
      {
        $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs = mysqli_fetch_array( $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws, MYSQLI_NUM );
        $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs = $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs[ 0 ];
      }
      return $vhFTvdzxPpZQiQAJJtlRbuPQHrcAITvcNdrSEJs;
    }
    return false;
  }

  public function affected_rows ( )
  {
    return mysqli_affected_rows( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM );
  }

  public function num_fields ( )
  {
    return mysqli_num_fields( $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws );
  }

  public function inserted_id ( )
  {
    return mysqli_insert_id( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM );
  }

  public function num_rows ( )
  {
    return mysqli_num_rows( $this->CMhvQGjGDCfCqMKXlWxUUPihwPzVEYtVGEws );
  }
  public function escapemysqlstring($string)
  {
   return mysqli_real_escape_string( $this->scrxBkWdHgCSLBsJQxEynAXUhdSMmIjpilM, $string );
  }
}
?>
