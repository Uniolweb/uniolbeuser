/**
 * Module: TYPO3/CMS/Brofix/Brofix
 *
 * Is included via Configuration/JavaScriptModules.php
 */

import $ from 'jquery';

class Uniolbeuser {

  constructor()
  {
    this.initialize();
    this.addEvemtListeners();

  }

  initialize()
  {
  }

  addEvemtListeners()
  {
    $('#zugriffsrechte b:first-child, #zugriffsrechte span.zwischen')
      .click(function(){
        $(this).closest('li').toggleClass('zu');
      })
      .each(function(){ if($(this).siblings('ul').length) { $(this).addClass('hasUl') }});

    $('#alleauf').click(function(){
      $('#zugriffsrechte li.zu').removeClass('zu');
    });

    $('#allezu').click(function(){
      $('#zugriffsrechte li li').addClass('zu');
    });

    $('.begroup').click(function(){
      $(this).parent().toggleClass('zeige');
    });

    $('#seite').keyup(function(e){
      var code = e.key; // recommended to use e.key, it's normalized across devices and languages
      if(code==="Enter") {
        e.preventDefault();
        window.location.href = window.location.pathname + '?seite=' + $('#seite').val();
      }
    });

    $('[tabindex="0"]').keydown(function(e){
      if(e.key==="Enter") {
        $(this).click();
      }
    });
  }

  urlParam(name)
  {
      let results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
      if (results==null){
        return '';
      }
      else{
        return results[1] || 0;
      }
  }


}

export default new Uniolbeuser;
