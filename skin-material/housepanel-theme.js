var MDCDialog, MDCSlider, textFieldWallpaper, settingsDialog;
var thisVersion = '1.0.0';

$.getScript("https://cdn.jsdelivr.net/npm/js-cookie@2/src/js.cookie.min.js", function() {
	if ( Cookies.get('materialZoomMain') ) {
		$('body').css( 'font-size', Cookies.get('materialZoomMain') );
		$('#settings-dialog-zoom-main-current').text( ( Math.round((document.body.style.fontSize.split("em")[0])*100) ) + "%" )
	}
	if ( Cookies.get('materialZoomNav') ) {
		$('#roomtabs').css('font-size', Cookies.get('materialZoomNav'));
		$('#settings-dialog-zoom-nav-current').text( ( Math.round((document.getElementById('roomtabs').style.fontSize.split("em")[0])*100) ) + "%" )
	}
	if ( Cookies.get('materialZoomTiles') ) {
		$('.thing').css('width', Cookies.get('materialZoomTiles'));
		$('#settings-dialog-zoom-tiles-current').text( ( Math.round((document.querySelector('.thing').style.width.split("em")[0])*10) ) + "%" )
	}
	if ( Cookies.get('materialHeatingOnly') ) {
		setHeatingOnlyHeaters( Cookies.get('materialHeatingOnly').split(',') );
		$('#heater-only-input').val( Cookies.get('materialHeatingOnly') );
	}
	if ( Cookies.get('materialSwitchOnly') ) {
		setSwitchOnlySwitches( Cookies.get('materialSwitchOnly').split(',') );
		$('#switch-only-input').val( Cookies.get('materialSwitchOnly') );
	}
	if ( Cookies.get('materialHideBackroundColor')=='true' || Cookies.get('materialHideBackroundColor')=='false' ) {
		hideBackgroundColor( (Cookies.get('materialHideBackroundColor') == 'true') );
		document.getElementById('hide-background-color-switch').checked = (Cookies.get('materialHideBackroundColor') == 'true');
	}
	if ( Cookies.get('materialWallpaperShadow')=='true' || Cookies.get('materialWallpaperShadow')=='false' ) {
		setWallpaperShadow( (Cookies.get('materialWallpaperShadow') == 'true') );
		document.getElementById('wallpaper-shadow-switch').checked = (Cookies.get('materialWallpaperShadow') == 'true');
	}
	if ( Cookies.get('materialWallpaperUrl') ) {
		$('body').css('background-image', 'url('+ Cookies.get('materialWallpaperUrl') +')');
		$('#wallpaper-input').val( Cookies.get('materialWallpaperUrl') );
	}
});
$.getScript("https://unpkg.com/material-components-web@latest/dist/material-components-web.js", function() {
	MDCDialog = mdc.dialog.MDCDialog;
	MDCSlider = mdc.slider.MDCSlider;

	settingsDialog = new MDCDialog(document.getElementById('mdc-dialog-with-list'));
	var MDCTextField = mdc.textField.MDCTextField;
	var textFieldSwitch = new MDCTextField(document.getElementById('switch-only-tf'));
	var textFieldHeater = new MDCTextField(document.getElementById('heater-only-tf'));
	var textFieldWallpaper = new MDCTextField(document.getElementById('wallpaper-tf'));

	$('#mdc-dialog-with-list > .mdc-dialog__backdrop').click(function() {
		tilesUpdate();
	});

	replaceLevelSliders();

	if ( Cookies.get('materialKelvinSliderObjects') ) {
		parseKelvinSliderObjectFromJson( Cookies.get('materialKelvinSliderObjects') );
	} //Coz we're editing MDC inputs from cookies, mdc stuff needs to be loaded

	if ( Cookies.get('materialMJPEGObjects') ) {
		parseMJPEGObjectFromJson( Cookies.get('materialMJPEGObjects') );
	}
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
	fixSlidersFirst();
	addWallpaperSettings();

	toggles.each(function() {
		toggleStatusToggle(this);
//	 	$(this).on( 'DOMSubtreeModified', function() {
//	 		toggleStatusToggle(this);
//	 	});
	});

	presence.each(function() {
		toggleStatusPresence(this);
//	 	$(this).on( 'DOMSubtreeModified', function() {
//	 		toggleStatusPresence(this);
//	 	});
	});

	musicThings.each(function() {
		fixMusicThing(this);
	});

	musicStatus.each(function() {
		toggleStatusMusicStatus(this);
//	 	$(this).on( 'DOMSubtreeModified', function() {
//	 		toggleStatusMusicStatus(this);
//	 	});
	});

	musicMutes.each(function() {
		toggleMuteMusicMute(this);
//	 	$(this).on( 'DOMSubtreeModified', function() {
//	 		toggleMuteMusicMute(this);
//	 	});
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
			//console.log($(musicMute).parent().parent().find('.music-mute-status'));
			$(musicMute).parent().parent().find('.music-mute-status').removeClass('music-mute-unmuted').addClass("music-mute-muted")
		}
	}
	if ( $(musicMute).text()=="unmuted" ) {
		if ( !$(musicMute).parent().parent().find('.music-mute-status').hasClass("music-mute-unmuted") ) {
			//console.log($(musicMute).parent().parent().find('.music-mute-status'));
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

	var before = $(musicControls).parent().find('.overlay.level')
	$(before).after(musicControls);

	before = $(musicControls).find('.music-next')
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

	$('#settings-dialog-zoom-tiles').after('<div class="button-default" onClick="zoomInTiles();">+</div>')
	$('#settings-dialog-zoom-tiles').after('<div id="settings-dialog-zoom-tiles-current" onClick="zoomResetTiles();" style="display: inline;">120%</div>')
	$('#settings-dialog-zoom-tiles').after('<div class="button-default" onClick="zoomOutTiles();">-</div>')
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

function zoomInTiles() {
	$('.thing').css('width', '+=1em');
	$('#settings-dialog-zoom-tiles-current').text( ( Math.round((document.querySelector('.thing').style.width.split("em")[0])*10) ) + "%" )
	Cookies.set( 'materialZoomTiles', document.querySelector('.thing').style.width )
}

function zoomOutTiles() {
	$('.thing').css('width', '-=1em');
	$('#settings-dialog-zoom-tiles-current').text( ( Math.round((document.querySelector('.thing').style.width.split("em")[0])*10) ) + "%" )
	Cookies.set( 'materialZoomTiles', document.querySelector('.thing').style.width )
}

function zoomResetTiles() {
	$('.thing').css('width', '12em');
	$('#settings-dialog-zoom-tiles-current').text( ( Math.round((document.querySelector('.thing').style.width.split("em")[0])*10) ) + "%" )
	Cookies.set( 'materialZoomTiles', document.querySelector('.thing').style.width )
}

function addFab() {
	$('.maintable').append('<button class="mdc-fab material-icons" aria-label="Favorite" id="hp-material-fab" data-mdc-auto-init="MDCRipple"><span class="mdc-fab__icon"><i class="material-icons">settings</i></span></button>');
        $("#hp-material-fab").on("click",function(evt) {
           $("#mdc-dialog-with-list-description").show();
           $("aside.mdc-dialog").show().css("z-index","999");
           evt.stopPropagation();
        });
        $("#mdc-dialog-with-list").on("click",function(evt) {
           $("aside.mdc-dialog").hide();
           $("#mdc-dialog-with-list-description").hide();
           evt.stopPropagation();
        });
}

function addSettingsDialog() {
	$('.maintable').append('<aside id="mdc-dialog-with-list" class="mdc-dialog" role="alertdialog" aria-labelledby="mdc-dialog-with-list-label" aria-describedby="mdc-dialog-with-list-description"><div class="mdc-dialog__surface"><section id="mdc-dialog-with-list-description" class="mdc-dialog__body mdc-dialog__body--scrollable"><h3 class="mdc-list-group__subheader" id="settings-dialog-hp">House panel</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-display">Display settings</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-zoom-main">Zoom main view</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-zoom-nav">Zoom nav bar</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-zoom-tiles">Zoom tiles</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-tiles">Tile settings (ids separated by commas)</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-sliders">Tile slider settings</h3><div class="button-default" onclick="createKelvinInput();">+</div><h3 class="mdc-list-group__subheader" id="settings-dialog-mjpeg">MJPEG camera stream</h3><div class="button-default" onclick="createMJPEGInput();">+</div><h3 class="mdc-list-group__subheader" id="settings-dialog-wallpaper">Wallpaper</h3><h3 class="mdc-list-group__subheader" id="settings-dialog-version">Version skin-material</h3><p style="margin: 0.75rem 16px;">You are running v'+thisVersion+'. <a href="https://github.com/vervallsweg/HousePanel-skin-material#latest-version">Check for updates.</a></p></section></div><div class="mdc-dialog__backdrop"></div></aside>')
	$('#settings-dialog-hp').after( $('.maintable > form') );
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
	//$('#settings-dialog-tiles').after('<div class="button-default" id="tiles-update" onClick="tilesUpdate();"><i class="material-icons">save</i></div>');
}

function tilesUpdate() {
	setHeatingOnlyHeaters( $('#heater-only-input').val().split(',') );
	setSwitchOnlySwitches( $('#switch-only-input').val().split(',') );
	hideBackgroundColor( document.getElementById('hide-background-color-switch').checked );
	setWallpaperShadow( document.getElementById('wallpaper-shadow-switch').checked );
	$('body').css('background-image', 'url('+ $('#wallpaper-input').val() +')');

	Cookies.set( 'materialHeatingOnly', $('#heater-only-input').val() );
	Cookies.set( 'materialSwitchOnly', $('#switch-only-input').val() );
	Cookies.set( 'materialHideBackroundColor', document.getElementById('hide-background-color-switch').checked );
	Cookies.set( 'materialWallpaperShadow', document.getElementById('wallpaper-shadow-switch').checked );
	Cookies.set( 'materialWallpaperUrl', $('#wallpaper-input').val() );

	parseKelvinSliderObject();
	parseMJPEGObject();
}

function replaceLevelSliders() {
	$('.ui-slider').css('display', 'none');
	$('.overlay.level').find('.ui-slider').parent().append('<div class="slider-icon-container"><i class="material-icons">brightness_medium</i></div><div class="mdc-slider mdc-slider--discrete" data-mdc-auto-init="MDCSlider" tabindex="0" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-step="1" aria-label="Select Value"><div class="mdc-slider__track-container"><div class="mdc-slider__track"></div></div><div class="mdc-slider__thumb-container"><div class="mdc-slider__pin"><span class="mdc-slider__pin-value-marker"></span></div><svg class="mdc-slider__thumb" width="21" height="21"><circle cx="10.5" cy="10.5" r="7.875"></circle></svg><div class="mdc-slider__focus-ring"></div></div></div>');
	$('.overlay.colorTemperature').find('.ui-slider').parent().append('<div class="slider-icon-container"><i class="material-icons">whatshot</i></div><div class="mdc-slider mdc-slider--discrete" data-mdc-auto-init="MDCSlider" tabindex="0" role="slider" aria-valuemin="20" aria-valuemax="60" aria-valuenow="20" data-step="1" aria-label="Select Value"><div class="mdc-slider__track-container"><div class="mdc-slider__track"></div></div><div class="mdc-slider__thumb-container"><div class="mdc-slider__pin"><span class="mdc-slider__pin-value-marker"></span></div><svg class="mdc-slider__thumb" width="21" height="21"><circle cx="10.5" cy="10.5" r="7.875"></circle></svg><div class="mdc-slider__focus-ring"></div></div></div>');
	$('.thing.music-thing > .overlay.level > .slider-icon-container > i').html('volume_up') //Fix music slider icon

	$('.mdc-slider').each(function() {
		var levelSlider = new MDCSlider(this);
		var initVal = $(this).parent().find('.ui-slider').attr('value')
		if (initVal>100) {initVal=initVal/100};
		levelSlider.value = initVal; //Get from actual slider
        var x = null;
		levelSlider.listen('MDCSlider:change', (x) => syncLevelSliders( levelSlider.root_ )); //MDC bug: fires twice for each value change 
	});

	$('.overlay.level > .ui-slider').each(function() {
		$(this).on("slidechange", function(event, ui) {
			syncLevelSliders(event.target);
		});
	});

	$( "#tabs" ).on("tabsactivate", function(event, ui) { 
		$(ui.newPanel).find('.mdc-slider').each(function() {
			//console.log('this.MDCSlider.layout()');
			this.MDCSlider.layout();
		});
	});

	window.mdc.autoInit();
}

function syncLevelSliders(slider) {
	if ( $(slider).hasClass('mdc-slider') ) {

		var newVal = slider.MDCSlider.value
		if ( $(slider).parent().hasClass('colorTemperature') ) { newVal = newVal*100; }
		
		if ( newVal != $(slider).parent().find('.ui-slider').slider("option", "value") ) {
			console.log('updating ui-slider from mdc-slider "#'+ $(slider).attr('id') +'", from: ' + $(slider).parent().find('.ui-slider').slider("option", "value") + ", to newVal: " + newVal);
			var uiSlider = $(slider).parent().find('.ui-slider').slider();
			uiSlider.slider('option', 'value', newVal);
			uiSlider.slider('option', 'stop').call(uiSlider, {target: $(slider).parent().find('.ui-slider'), value: newVal}, { handle: $('.ui-slider-handle', uiSlider), value: newVal });
		}
	}

	if ( $(slider).hasClass('ui-slider') ) {
		
		var newVal = $(slider).slider("option", "value")
		if ( $(slider).parent().hasClass('colorTemperature') ) { newVal = newVal/100; }

		if ( newVal != $(slider).parent().find('.mdc-slider')[0].MDCSlider.value ) {
			console.log('updating mdc-slider from ui-slider "#'+ $(slider).attr('id') +'", from: ' + $(slider).parent().find('.mdc-slider')[0].MDCSlider.value + ", to newVal: " + newVal);
			$(slider).parent().find('.mdc-slider')[0].MDCSlider.value = newVal;
		}
	}
}

function createKelvinInput() {
	var sliderGroup = document.createElement("div");
	$(sliderGroup).addClass("kelvin-sliders-group");
	$(sliderGroup).append('<div class="mdc-text-field kelvin-sliders-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input kelvin-sliders-input"><label class="mdc-text-field__label">Sliders</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="mdc-text-field kelvin-min-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input kelvin-min-input"><label class="mdc-text-field__label">Min vlaue</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="mdc-text-field kelvin-max-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input kelvin-max-input"><label class="mdc-text-field__label">Max value</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="mdc-text-field kelvin-interval-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input kelvin-interval-input"><label class="mdc-text-field__label">Interval</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="button-default" onclick="removeKelvinInput(this);">-</div>');
	$('#settings-dialog-sliders').after(sliderGroup);
	window.mdc.autoInit();
}

function removeKelvinInput(removeButton) {
	$(removeButton).parent().remove();
}

function parseKelvinSliderObject() {
	var kelvinSliderObjects = [];
	$('.kelvin-sliders-group').each(function() {
		var currentKelvinSliderObject = {
			sliders: $(this).find('.kelvin-sliders-input').val(),
			min: $(this).find('.kelvin-min-input').val(),
			max: $(this).find('.kelvin-max-input').val(),
			interval: $(this).find('.kelvin-interval-input').val()
		}
		kelvinSliderObjects.push( currentKelvinSliderObject );
		setKelvinSliderOptions( currentKelvinSliderObject );
	});
	Cookies.set( 'materialKelvinSliderObjects', JSON.stringify(kelvinSliderObjects) );
}

function hasEmptyKey(kelvinSliderObject) {
	if (kelvinSliderObject.sliders == '') { return true; }
	if (kelvinSliderObject.min == '') { return true; }
	if (kelvinSliderObject.max == '') { return true; }
	if (kelvinSliderObject.interval == '') { return true; }
	return false;
}

function setKelvinSliderOptions(kelvinSliderObject) {
	if ( !hasEmptyKey(kelvinSliderObject) ) {
		kelvinSliderObject.sliders.split(',').forEach(function(element) {
  			var currentSlider = $('#'+element+' > .overlay.colorTemperature > .mdc-slider');
  			currentSlider[0].MDCSlider.min = parseInt(kelvinSliderObject.min, 10);
  			currentSlider[0].MDCSlider.max = parseInt(kelvinSliderObject.max, 10);
  			currentSlider[0].MDCSlider.step = parseInt(kelvinSliderObject.interval, 10);
		});
	}
}

function parseKelvinSliderObjectFromJson(json) {
	JSON.parse(json).forEach(function(element, index) {
		createKelvinInput();
		$('.kelvin-sliders-group').children(index).find('.kelvin-sliders-input').val(element.sliders);
		$('.kelvin-sliders-group').children(index).find('.kelvin-min-input').val(element.min);
		$('.kelvin-sliders-group').children(index).find('.kelvin-max-input').val(element.max);
		$('.kelvin-sliders-group').children(index).find('.kelvin-interval-input').val(element.interval);
		setKelvinSliderOptions(element);
	});
}

function fixSlidersFirst() {
	$('.overlay.switch').each(function() {
		$(this).after( $(this).parent().find('.overlay.colorTemperature') );
		$(this).after( $(this).parent().find('.overlay.level') );
	});
}

function addWallpaperSettings() {
	$('#settings-dialog-wallpaper').after('<div class="mdc-text-field" id="wallpaper-tf"><input type="text" id="wallpaper-input" class="mdc-text-field__input"><label class="mdc-text-field__label" for="my-text-field">Wallpaper URL</label><div class="mdc-line-ripple"></div></div>');
	$('#wallpaper-tf').after('<div class="mdc-switch"><input type="checkbox" id="wallpaper-shadow-switch" class="mdc-switch__native-control" /><div class="mdc-switch__background"><div class="mdc-switch__knob"></div></div></div><label for="basic-switch" class="mdc-switch-label">Wallpaper shadow</label>');
}

function setWallpaperShadow(bool) {
	if (bool) {
		$('div.maintable').css('background-color', 'rgba(0,0,0,0.5)');
	} else {
		$('div.maintable').css('background-color', '');
	}
}

function createMJPEGInput() {
	var sliderGroup = document.createElement("div");
	$(sliderGroup).addClass("mjpeg-group");
	$(sliderGroup).append('<div class="mdc-text-field mjpeg-id-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input mjpeg-id-input"><label class="mdc-text-field__label">IDs to attach to</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="mdc-text-field mjpeg-name-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input mjpeg-name-input"><label class="mdc-text-field__label">Tile name</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="mdc-text-field mjpeg-url-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input mjpeg-url-input"><label class="mdc-text-field__label">Stream URL</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="mdc-text-field mjpeg-width-tf" data-mdc-auto-init="MDCTextField"><input type="text" class="mdc-text-field__input mjpeg-width-input"><label class="mdc-text-field__label">Width</label><div class="mdc-line-ripple"></div></div>');
	$(sliderGroup).append('<div class="button-default" onclick="removeKelvinInput(this);">-</div>');
	$('#settings-dialog-mjpeg').after(sliderGroup);
	window.mdc.autoInit();
}

function parseMJPEGObject() {
	var MJPEGObjects = [];
	$('.mjpeg-group').each(function() {
		var currentMJPEGObject = {
			ids: $(this).find('.mjpeg-id-input').val(),
			url: $(this).find('.mjpeg-url-input').val(),
			width: $(this).find('.mjpeg-width-input').val(),
			name: $(this).find('.mjpeg-name-input').val()
		}
		MJPEGObjects.push( currentMJPEGObject );
		addMJPEGImage( currentMJPEGObject );
	});
	Cookies.set( 'materialMJPEGObjects', JSON.stringify(MJPEGObjects) );
}

function addMJPEGImage(MJPEGObject) {
	MJPEGObject.ids.split(',').forEach(function(element) {
		if ( $('#'+element+' > img').length<1 ) {
			$('#'+element).append('<img src="'+MJPEGObject.url+'" style="width: '+MJPEGObject.width+'"></img>');
			$('#'+element).find('.thingname > span').text(MJPEGObject.name);
		}
	});
}

function parseMJPEGObjectFromJson(json) {
	JSON.parse(json).forEach(function(element, index) {
		createMJPEGInput();
		$('.mjpeg-group').children(index).find('.mjpeg-id-input').val(element.ids);
		$('.mjpeg-group').children(index).find('.mjpeg-url-input').val(element.url);
		$('.mjpeg-group').children(index).find('.mjpeg-width-input').val(element.width);
		$('.mjpeg-group').children(index).find('.mjpeg-name-input').val(element.name);
		addMJPEGImage(element);
	});
}