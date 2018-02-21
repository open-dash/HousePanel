var MDCDialog, MDCSlider, levelDialog, levelSlider;

$.getScript("https://cdn.jsdelivr.net/npm/js-cookie@2/src/js.cookie.min.js", function() {
	if ( Cookies.get('materialZoomMain') ) {
		$('body').css( 'font-size', Cookies.get('materialZoomMain') );
		$('#settings-dialog-zoom-main-current').text( ( Math.round((document.body.style.fontSize.split("em")[0])*100) ) + "%" )
	}
	if ( Cookies.get('materialZoomNav') ) {
		$('#roomtabs').css('font-size', Cookies.get('materialZoomNav'));
		$('#settings-dialog-zoom-nav-current').text( ( Math.round((document.getElementById('roomtabs').style.fontSize.split("em")[0])*100) ) + "%" )
	}
	if ( Cookies.get('materialHeatingOnly') ) {
		setHeatingOnlyHeaters( Cookies.get('materialHeatingOnly').split(',') );
		$('#heater-only-input').val( Cookies.get('materialHeatingOnly') );
	}
	if ( Cookies.get('materialSwitchOnly') ) {
		setSwitchOnlySwitches( Cookies.get('materialSwitchOnly').split(',') );
		$('#switch-only-input').val( Cookies.get('materialSwitchOnly') );
	}
	if ( Cookies.get('materialHideBackroundColor')==true || Cookies.get('materialHideBackroundColor')==false ) {
		hideBackgroundColor( Cookies.get('materialHideBackroundColor') );
		document.getElementById('hide-background-color-switch').checked = Cookies.get('materialHideBackroundColor');
	}
});
$.getScript("https://unpkg.com/material-components-web@latest/dist/material-components-web.js", function() {
	MDCDialog = mdc.dialog.MDCDialog;
	MDCSlider = mdc.slider.MDCSlider;
	
	$('body').append('<aside id="level-dialog" class="mdc-dialog" role="alertdialog" aria-labelledby="level-dialog-label" aria-describedby="level-dialog-description"><div class="mdc-dialog__surface"><header class="mdc-dialog__header"><h2 id="level-dialog-label" class="mdc-dialog__header__title">Device name</h2></header><section id="level-dialog-description" class="mdc-dialog__body"></section></div><div class="mdc-dialog__backdrop"></div></aside>');
	//$('body').append('<aside id="color-temp-dialog" class="mdc-dialog" role="alertdialog" aria-labelledby="color-temp-dialog-label" aria-describedby="color-temp-dialog-description"><div class="mdc-dialog__surface"><header class="mdc-dialog__header"><h2 id="color-temp-dialog-label" class="mdc-dialog__header__title">Device name</h2></header><section id="color-temp-dialog-description" class="mdc-dialog__body"></section></div><div class="mdc-dialog__backdrop"></div></aside>');
	
	$(".level-val").parent().on( 'click', openLevelDialog);
	//$(".colorTemperature-val").parent().on( 'click', openColorTempDialog);

	levelDialog = new MDCDialog(document.querySelector('#level-dialog'));

	var MDCTextField = mdc.textField.MDCTextField;
	var textFieldSwitch = new MDCTextField(document.getElementById('switch-only-tf'));
	var textFieldHeater = new MDCTextField(document.getElementById('heater-only-tf'));
});

$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', 'https://fonts.googleapis.com/icon?family=Material+Icons') );
$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', 'https://unpkg.com/material-components-web@latest/dist/material-components-web.css') );

$( document ).ready(function() {
	var toggles = $('.on, .off');
	var presence = $('.present');
	var musicThings = $('.music-thing');
	var musicStatus = $('.music.musicstatus');
	var musicMutes = $('.music.musicmute');

	addSettingsDialog();
	addFullScreenButton();
	addZoomButtons();
	addFab();
	addTilesUpdateButton();
	addHideBackgroundColorSwitch();
	addSwitchOnlyInput();
	addHeaterOnlyInput();

	toggles.each(function() {
		toggleStatusToggle(this);
	 	$(this).on( 'DOMSubtreeModified', function() {
	 		toggleStatusToggle(this);
	 	});
	});

	presence.each(function() {
		toggleStatusPresence(this);
	 	$(this).on( 'DOMSubtreeModified', function() {
	 		toggleStatusPresence(this);
	 	});
	});

	musicThings.each(function() {
		fixMusicThing(this);
	});

	musicStatus.each(function() {
		toggleStatusMusicStatus(this);
	 	$(this).on( 'DOMSubtreeModified', function() {
	 		toggleStatusMusicStatus(this);
	 	});
	});

	musicMutes.each(function() {
		toggleMuteMusicMute(this);
	 	$(this).on( 'DOMSubtreeModified', function() {
	 		toggleMuteMusicMute(this);
	 	});
	});
});

function toggleStatusToggle(toggle) {
	var parent = $(toggle).parent().parent();

	if ( $(toggle).hasClass("on") ) {
		console.log('toggleStatusToggle(): on');
		if ( !$(parent).hasClass("thing-on") ) {
			console.log('toggleStatusToggle(): setting thing-on');
			$(parent).removeClass('thing-off').addClass("thing-on")
		}
	}
	if ( $(toggle).hasClass("off") ) {
		console.log('toggleStatusToggle(): off');
		if ( !$(parent).hasClass("thing-off") ) {
			console.log('toggleStatusToggle(): setting thing-off');
			$(parent).removeClass('thing-on').addClass("thing-off")
		}
	}
}

function toggleStatusPresence(presence) {
	var parent = $(presence).parent().parent();

	if ( $(presence).hasClass("present") ) {
		console.log('toggleStatusToggle(): present');
		if ( !$(parent).hasClass("thing-on") ) {
			console.log('toggleStatusToggle(): setting thing-on');
			$(parent).removeClass('thing-off').addClass("thing-on")
		}
	}
	if ( $(presence).hasClass("absent") ) {
		console.log('toggleStatusToggle(): absent');
		if ( !$(parent).hasClass("thing-off") ) {
			console.log('toggleStatusToggle(): setting thing-off');
			$(parent).removeClass('thing-on').addClass("thing-off")
		}
	}
}

function toggleStatusMusicStatus(musicStatus) {
	var parent = $(musicStatus).parent().parent();

	if ( $(musicStatus).hasClass("playing") || $(musicStatus).text()=="group" ) {
		console.log('toggleStatusMusicStatus(): active');
		if ( $(musicStatus).text()=="group" ) {
			if ( !$(musicStatus).hasClass("group") ) {
				$(musicStatus).addClass("group");
			}
		}
		if ( !$(parent).hasClass("thing-on") ) {
			console.log('toggleStatusMusicStatus(): setting thing-on');
			$(parent).removeClass('thing-off').addClass("thing-on")
		}
	}
	if ( $(musicStatus).hasClass("paused") || $(musicStatus).hasClass("stopped") || $(musicStatus).hasClass("Ready") ) {
		console.log('toggleStatusMusicStatus(): nActive');
		if ( !$(parent).hasClass("thing-off") ) {
			console.log('toggleStatusMusicStatus(): setting thing-off');
			$(parent).removeClass('thing-on').addClass("thing-off")
		}
	}
}

function toggleMuteMusicMute(musicMute) {
	if ( $(musicMute).text()=="muted" ) {
		if ( !$(musicMute).parent().parent().find('.music-mute-status').hasClass("music-mute-muted") ) {
			console.log($(musicMute).parent().parent().find('.music-mute-status'));
			$(musicMute).parent().parent().find('.music-mute-status').removeClass('music-mute-unmuted').addClass("music-mute-muted")
		}
	}
	if ( $(musicMute).text()=="unmuted" ) {
		if ( !$(musicMute).parent().parent().find('.music-mute-status').hasClass("music-mute-unmuted") ) {
			console.log($(musicMute).parent().parent().find('.music-mute-status'));
			$(musicMute).parent().parent().find('.music-mute-status').removeClass('music-mute-muted').addClass("music-mute-unmuted")
		}
	}
}

function fixMusicThing(musicThing) {
	fixMusicControls( $(musicThing).find('.music-controls') );
	fixMusicStatus( $(musicThing).find('.overlay.musicstatus') );
	fixMusicMute( $(musicThing).find('.overlay.musicmute') );
	$(musicThing).find('.overlay.track').css('margin', '-1em 0.5em 1em 0.5em').css('color', '#ffffff');
}

function fixMusicControls(musicControls) {
	$(musicControls).addClass("music-controls-fixed");
	var after = $(musicControls).parent().find('.level')
	$(after).before(musicControls);
	var before = $(musicControls).find('.music-next')
	$(before).after('<div class="music-mute-status music-mute-unmuted"></div>');
}

function fixMusicStatus(musicStatus) {
	var before = $(musicStatus).parent().find('.thingname')
	$(before).after(musicStatus);
}

function fixMusicMute(musicMute) {
	var before = $(musicMute).parent().find('.thingname')
	$(before).after(musicMute);
	$(musicMute).css('display', 'none');
}

function setHeatingOnlyHeaters(ids) {
	$(ids).each(function() {
		$("#"+this).addClass('thermostat-thing-heater-only');
	});
}

function setSwitchOnlySwitches(ids) {
	$(ids).each(function() {
		$("#"+this).find('div').each(function() {
			if ( !$(this).hasClass('thingname') && !$(this).hasClass('name') && !$(this).hasClass('overlay switch') ) {
				if ( !$(this).parent().hasClass('overlay switch') ) {
					$(this).css('display', 'none');
				}
			}
		})
	});
}

function hideBackgroundColor(bool) {
	if (bool) {
		$('.overlay').find('div').css('background-color', '');
	}
}

function addFullScreenButton() {
	$('#settings-dialog-display').after('<div class="button-default" onClick="enterFullscreen(document.documentElement);">Fullscreen</div>')
	$('#settings-dialog-display').after('<div class="button-default" onClick="exitFullscreen();">Exit fullscreen</div>')
}

function enterFullscreen(element) {
  if(element.requestFullscreen) {
    element.requestFullscreen();
  } else if(element.mozRequestFullScreen) {
    element.mozRequestFullScreen();
  } else if(element.msRequestFullscreen) {
    element.msRequestFullscreen();
  } else if(element.webkitRequestFullscreen) {
    element.webkitRequestFullscreen();
  }
}

function exitFullscreen() {
  if(document.exitFullscreen) {
    document.exitFullscreen();
  } else if(document.mozCancelFullScreen) {
    document.mozCancelFullScreen();
  } else if(document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  }
}

function addZoomButtons() {
	$('#settings-dialog-zoom-main').after('<div class="button-default" onClick="zoomInMain();">+</div>')
	$('#settings-dialog-zoom-main').after('<div id="settings-dialog-zoom-main-current" onClick="zoomResetMain();" style="display: inline;">100%</div>')
	$('#settings-dialog-zoom-main').after('<div class="button-default" onClick="zoomOutMain();">-</div>')

	$('#settings-dialog-zoom-nav').after('<div class="button-default" onClick="zoomInNav();">+</div>')
	$('#settings-dialog-zoom-nav').after('<div id="settings-dialog-zoom-nav-current" onClick="zoomResetNav();" style="display: inline;">85%</div>')
	$('#settings-dialog-zoom-nav').after('<div class="button-default" onClick="zoomOutNav();">-</div>')
}

function zoomInMain() {
	$('body').css('font-size', '+=0.1em');
	$('#settings-dialog-zoom-main-current').text( ( Math.round((document.body.style.fontSize.split("em")[0])*100) ) + "%" )
	Cookies.set( 'materialZoomMain', document.body.style.fontSize )
}

function zoomOutMain() {
	$('body').css('font-size', '-=0.1em');
	$('#settings-dialog-zoom-main-current').text( ( Math.round((document.body.style.fontSize.split("em")[0])*100) ) + "%" )
	Cookies.set( 'materialZoomMain', document.body.style.fontSize )
}

function zoomResetMain() {
	$('body').css('font-size', '1em');
	$('#settings-dialog-zoom-main-current').text( ( Math.round((document.body.style.fontSize.split("em")[0])*100) ) + "%" )
	Cookies.set( 'materialZoomMain', document.body.style.fontSize )
}

function zoomInNav() {
	$('#roomtabs').css('font-size', '+=0.05em');
	$('#settings-dialog-zoom-nav-current').text( ( Math.round((document.getElementById('roomtabs').style.fontSize.split("em")[0])*100) ) + "%" )
	Cookies.set( 'materialZoomNav', document.getElementById('roomtabs').style.fontSize )
}

function zoomOutNav() {
	$('#roomtabs').css('font-size', '-=0.05em');
	$('#settings-dialog-zoom-nav-current').text( ( Math.round((document.getElementById('roomtabs').style.fontSize.split("em")[0])*100) ) + "%" )
	Cookies.set( 'materialZoomNav', document.getElementById('roomtabs').style.fontSize )
}

function zoomResetNav() {
	$('#roomtabs').css('font-size', '0.85em');
	$('#settings-dialog-zoom-nav-current').text( ( Math.round((document.getElementById('roomtabs').style.fontSize.split("em")[0])*100) ) + "%" )
	Cookies.set( 'materialZoomNav', document.getElementById('roomtabs').style.fontSize )
}

function addFab() {
	$('.maintable').append('<button class="mdc-fab material-icons" aria-label="Favorite" id="hp-material-fab" data-mdc-auto-init="MDCRipple" onClick="showSettingsDialog();"><span class="mdc-fab__icon"><i class="material-icons">settings</i></span></button>');
}

function addSettingsDialog() {
	$('.maintable').append('<aside id="mdc-dialog-with-list" class="mdc-dialog" role="alertdialog" aria-labelledby="mdc-dialog-with-list-label" aria-describedby="mdc-dialog-with-list-description"><div class="mdc-dialog__surface"><section id="mdc-dialog-with-list-description" class="mdc-dialog__body mdc-dialog__body--scrollable"><h3 class="mdc-list-group__subheader" id="settings-dialog-hp">House panel</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-display">Display settings</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-zoom-main">Zoom main view</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-zoom-nav">Zoom nav bar</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-tiles">Tile settings (ids separated by commas)</h3></section></div><div class="mdc-dialog__backdrop"></div></aside>')
	$('#settings-dialog-hp').after( $('div.buttons') );
}

function showSettingsDialog() {
 	var dialog = new MDCDialog(document.getElementById('mdc-dialog-with-list'));
 	dialog.show();
}

function addHeaterOnlyInput() {
	$('#settings-dialog-tiles').after('<div class="mdc-text-field" id="heater-only-tf"><input type="text" id="heater-only-input" class="mdc-text-field__input"><label class="mdc-text-field__label" for="my-text-field">Heating only devices</label><div class="mdc-line-ripple"></div></div>');
}

function addSwitchOnlyInput() {
	$('#settings-dialog-tiles').after('<div class="mdc-text-field" id="switch-only-tf"><input type="text" id="switch-only-input" class="mdc-text-field__input"><label class="mdc-text-field__label" for="my-text-field">Switch only devices</label><div class="mdc-line-ripple"></div></div>');
}

function addHideBackgroundColorSwitch() {
	$('#settings-dialog-tiles').after('<div class="mdc-switch"><input type="checkbox" id="hide-background-color-switch" class="mdc-switch__native-control" /><div class="mdc-switch__background"><div class="mdc-switch__knob"></div></div></div><label for="basic-switch" class="mdc-switch-label">Hide color</label>');
}

function addTilesUpdateButton() {
	$('#settings-dialog-tiles').after('<div class="button-default" id="tiles-update" onClick="tilesUpdate();"><i class="material-icons">save</i></div>');
}

function tilesUpdate() {
	setHeatingOnlyHeaters( $('#heater-only-input').val().split(',') );
	setSwitchOnlySwitches( $('#switch-only-input').val().split(',') );
	hideBackgroundColor( document.getElementById('hide-background-color-switch').checked );
	Cookies.set( 'materialHeatingOnly', $('#heater-only-input').val() );
	Cookies.set( 'materialSwitchOnly', $('#switch-only-input').val() );
	Cookies.set( 'materialHideBackroundColor', document.getElementById('hide-background-color-switch').checked );
}

function openLevelDialog() {
	var ajaxParams = {
		hmtoken: 	Cookies.get('hmtoken'),
		hmendpoint:	Cookies.get('hmendpoint'),
		aid: 		$(this).parent().attr('id').split('-')[1],
		bid: 		$(this).parent().attr('bid'),
		type: 		$(this).parent().attr('type')
	};

	$('#level-dialog-description').empty();
	$('#level-dialog-description').append('<div id="level-slider" class="mdc-slider mdc-slider--discrete" tabindex="0" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-step="1" aria-label="Select Value"><div class="mdc-slider__track-container"><div class="mdc-slider__track"></div></div><div class="mdc-slider__thumb-container"><div class="mdc-slider__pin"><span class="mdc-slider__pin-value-marker"></span></div><svg class="mdc-slider__thumb" width="21" height="21"><circle cx="10.5" cy="10.5" r="7.875"></circle></svg><div class="mdc-slider__focus-ring"></div></div></div>');
	

	levelSlider = new MDCSlider(document.querySelector('#level-slider'));
	levelSlider.value = $(this).find('.level-val').text();
	levelSlider.listen('MDCSlider:change', () => setLevel(ajaxParams, levelSlider.value)); //MDC bug: fires twice for each value change
	
	$('#level-dialog-label').text( $(this).parent().find('.overlay.name').text() );
	levelDialog.show();

	setTimeout(function(){
		levelSlider.layout();
	}, 100);
	setTimeout(function(){
		levelSlider.layout();
	}, 1000);
	setTimeout(function(){
		levelSlider.layout();
	}, 2000);
}

function openColorTempDialog() {
	var ajaxParams = {
		hmtoken: 	Cookies.get('hmtoken'),
		hmendpoint:	Cookies.get('hmendpoint'),
		aid: 		$(this).parent().attr('id').split('-')[1],
		bid: 		$(this).parent().attr('bid'),
		type: 		$(this).parent().attr('type')
	};

	$('#level-dialog-description').empty();
	$('#level-dialog-description').append('<div id="level-slider" class="mdc-slider mdc-slider--discrete" tabindex="0" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-step="1" aria-label="Select Value"><div class="mdc-slider__track-container"><div class="mdc-slider__track"></div></div><div class="mdc-slider__thumb-container"><div class="mdc-slider__pin"><span class="mdc-slider__pin-value-marker"></span></div><svg class="mdc-slider__thumb" width="21" height="21"><circle cx="10.5" cy="10.5" r="7.875"></circle></svg><div class="mdc-slider__focus-ring"></div></div></div>');
	

	levelSlider = new MDCSlider(document.querySelector('#level-slider'));
	levelSlider.value = $(this).find('.level-val').text();
	levelSlider.listen('MDCSlider:change', () => setLevel(ajaxParams, levelSlider.value)); //MDC bug: fires twice for each value change
	
	$('#level-dialog-label').text( $(this).parent().find('.overlay.name').text() );
	levelDialog.show();

	setTimeout(function(){
		levelSlider.layout();
	}, 100);
}

function setLevel(ajaxParams, targetLevel) {
	var targetState = 'off'; if (targetLevel>0) { targetState='on'; }
	$.post( "housepanel.php", { useajax: "doaction", hmtoken: ajaxParams.hmtoken, hmendpoint: ajaxParams.hmendpoint, id: ajaxParams.bid, type: ajaxParams.type, value: targetState, attr: targetLevel } );

	//Seemingly random, the device level is set, but the device level attribute in ST is not updated > fix: set level again, at some point the device will report uptaded level atribute
	setTimeout(function() {
		console.log('refreshTile 1s, aid: '+ajaxParams.aid+', bid: '+ajaxParams.bid+', type: '+ajaxParams.type);
		refreshTile(ajaxParams.aid, ajaxParams.bid, ajaxParams.type);
	}, 1000);
	setTimeout(function() {
		console.log('refreshTile 5s');
		refreshTile(ajaxParams.aid, ajaxParams.bid, ajaxParams.type);
	}, 5000);
}