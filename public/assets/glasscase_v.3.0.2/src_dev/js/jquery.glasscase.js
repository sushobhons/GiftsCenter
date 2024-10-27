/*!
 * VERSION: 3.0.1
 * DATE: 2018-04-06
 * DOCS AT: http://www.tinycomp.net/
 * 
 * @license Copyright (c) 2014-2018, tinyComp. All rights reserved.
 * 
 * @author: tinyComp, tinycomp@outlook.com
 **/
;
(function ($, window, document, undefined) {
    'use strict';

    var Modernizr = window.Modernizr;

    function GCLoader(colorLoading) {
        this.color = colorLoading;

        this.init = function () {
            var _this = this;

            var c;
            _this.pLoadingClass = 'gc-ploading';
            _this.gcLoadingClass = (Modernizr.csstransforms === true) ? 'gc-loading3' : 'gc-loading';
            _this.gcLoading = $('<div class="' + _this.pLoadingClass + '"><div class="' + _this.gcLoadingClass + '"></div></div>"');

            if (colorLoading !== -1 && Modernizr.csstransforms === true) {
                if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(colorLoading)) {
                    c = colorLoading.substring(1).split('');
                    if (c.length == 3) {
                        c = [c[0], c[0], c[1], c[1], c[2], c[2]];
                    }
                    c = '0x' + c.join('');
                    var sC = 'rgba(' + [(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',') + ',1)';
                    _this.gcLoading.find('.' + _this.gcLoadingClass).css({
                        'border-top-color': sC + '0.2)',
                        'border-right-color': sC + '0.2)',
                        'border-bottom-color': sC + '0.2)',
                        'border-left-color': sC + '1)'
                    });
                }

                var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(colorLoading);
                if (result) {
                    var sC = 'rgba(' + parseInt(result[1], 16) + ', ' + parseInt(result[2], 16) + ', ' + parseInt(result[3], 16) + ', ';
                    _this.gcLoading.find('.' + _this.gcLoadingClass).css({
                        'border-top-color': sC + '0.2)',
                        'border-right-color': sC + '0.2)',
                        'border-bottom-color': sC + '0.2)',
                        'border-left-color': sC + '1)'
                    });
                }
            }
        };
        this.getInstance = function () {
            return this.gcLoading;
        };

        //Execution
        this.init();
    }

    function GCImage(element, config) {
        //Properties
        this.type = 'image';
        this.width = 0;
        this.height = 0;
        this.url = $(element).attr('src'); //src
        this.altTxt = $(element).attr('alt') || 'image'; //alt
        this.caption = $(element).data('gc-caption') || '';
        this.state = 'new'; //new; loading; loaded; error;
        this.dfdState = $.Deferred();
        this.instanceCnt = [];
        this.instance = [];
        this.instanceDisplay = [];
        this.errorTxt = config.textImageNotLoaded;
        this.tmp = ['thumb', 'display', 'zoom', 'overlay'];
        this.loader = [];
        this.wDisplayR = 0;
        this.hDisplayR = 0;
        this.isMouseEventsOn = false;
        this.colorLoading = config.colorLoading;
        console.log('image');
        //Methods
        this.init = function () {
            var _this = this;

            $.each(_this.tmp, function (index, tmpI) {
                switch (tmpI) {
                    case 'zoom':
                        _this.instance.push($('<div class="gc-zoom-container">' +
                                '<img src=' + _this.url + ' alt=' + _this.altTxt + ' />' +
                                '</div>'));
                        break;
                    case 'display':
                        _this.instance.push($('<div class="gc-slide-container">' +
                                '<div class="gc-display-container">' +
                                '<img class="gc-display-display" src=' + _this.url + ' alt=' + _this.altTxt + ' />' +
                                '</div></div>'));
                        break;
                    case 'thumb':
                        _this.instance.push($('<div class="gc-display-container">' +
                                '<img class="gc-display-display" src=' + _this.url + ' alt=' + _this.altTxt + ' />' +
                                '</div>'));
                        break;
                    case 'overlay':
                        _this.instance.push($('<div class="gc-overlay-container-display">' +
                                '<img class="gc-overlay-display" src=' + _this.url + ' alt=' + _this.altTxt + ' />' +
                                '</div>'));
                        break;
                }

                _this.instanceDisplay.push(_this.instance[index].find('img').hide());
                _this.loader.push(new GCLoader(_this.colorLoading));
            });

            if (_this.state === 'new') {
                _this.state = 'loading';
                _this.addLoader();
                $.when(_this.preload()).done(function () {
                    if (_this.state === 'error') {
                        $.each(_this.instance, function (index) {
                            _this.instance[index].find('img').attr('src', _this.url);
                        });
                    }
                    _this.removeLoader();
                    _this.fitSize();
                    _this.dfdState.resolve();
                });
            }
        };
        this.getInstance = function (area) {
            var _this = this;

            return _this.instance[$.inArray(area, _this.tmp)];
        };
        this.getInstanceDisplay = function (area) {
            var _this = this;

            return _this.instanceDisplay[$.inArray(area, _this.tmp)];
        };
        this.preload = function () {
            var _this = this;

            return $.Deferred(
                    function (dfd) {
                        $('<img/>')
                                .on('load', function () {
                                    _this.width = this.width;
                                    _this.height = this.height;
                                    _this.state = 'loaded';

                                    dfd.resolve();
                                })
                                .on('error', function () {
                                    this.onerror = '';

                                    _this.state = 'error';
                                    if (Modernizr.svg) {
                                        //Is needed the width and height of the li container
                                        _this.width = 150;
                                        _this.height = 150;

                                        var iEDB64 = window.btoa('<svg xmlns="http://www.w3.org/2000/svg" width="' + _this.width + '" height="' + _this.height + '"><rect width="' + _this.width + '" height="' + _this.height + '" fill="#fff"/><text text-anchor="middle" x="' + _this.width / 2 + '" y="' + _this.height / 2 + '" style="fill:#333;font-weight:bold;font-size:16px;font-family:Arial,Helvetica,sans-serif;dominant-baseline:central">' + _this.errorTxt + '</text></svg>');
                                        _this.url = 'data:image/svg+xml;base64,' + iEDB64;
                                    }

                                    dfd.resolve();
                                }).attr('src', _this.url);
                    }
            ).promise();
        };
        this.fitSize = function () {
            var _this = this;
            console.log(_this);
            $.each(_this.instanceDisplay, function (index, el) {

                if (_this.tmp[index] === 'display') {
                    _this.instance[index].find('.gc-display-container').addClass('gc-dc-center');
                }

                if (_this.tmp[index] === 'thumb') {
                    console.log(_this.instance[index], _this.instance[index].width(), _this.width);
                    var wRatio = _this.instance[index].width() / _this.width;
                    var hRatio = _this.instance[index].height() / _this.height;
                    var ratioImg = wRatio > hRatio ? wRatio : hRatio;
                    el.width(Math.ceil(_this.width * ratioImg, 10));
                    el.height(Math.ceil(_this.height * ratioImg, 10));

                    var percMarginLeft = ((el.outerWidth() / 2) * 100) / (_this.instance[index].outerWidth());
                    var percMarginTop = ((el.outerHeight() / 2) * 100) / (_this.instance[index].outerWidth());

                    el.css({
                        'margin-top': "-" + percMarginTop + "%",
                        'margin-left': "-" + percMarginLeft + "%"
                    });
                }

                if (_this.tmp[index] === 'overlay') {
                    _this.instanceDisplay[index].removeClass('gc-overlay-display-hcenter gc-overlay-display-vcenter')
                            .addClass('gc-overlay-display-center');
                }
            });
        };
        this.isFitScr = function (width, height) {
            var _this = this;

            return (_this.width <= width) && (_this.height <= height);
        };
        this.showNF = function (displayType, width, height) { //displayType: fit [default], nat
            var _this = this;
            $.each(_this.tmp, function (index, tmpI) {
                if (tmpI === 'overlay') {
                    if (displayType === 'fit') {
                        _this.instanceDisplay[index].removeClass('gc-overlay-display-hcenter gc-overlay-display-vcenter')
                                .addClass('gc-overlay-display-center');
                    } else {
                        _this.instanceDisplay[index].removeClass('gc-overlay-display-center gc-overlay-display-hcenter gc-overlay-display-vcenter');

                        if (_this.width <= width && _this.height <= height) {
                            _this.instanceDisplay[index].addClass('gc-overlay-display-center');
                        } else {
                            if (_this.width <= width) {
                                _this.instanceDisplay[index].addClass('gc-overlay-display-hcenter');
                            }
                            if (_this.height <= height) {
                                _this.instanceDisplay[index].addClass('gc-overlay-display-vcenter');
                            }
                        }
                    }
                }
            });
        };
        this.addLoader = function () {
            var _this = this;
            $.each(_this.instanceDisplay, function (index, el) {
                $(el).before(_this.loader[index].getInstance());
            });
        };
        this.removeLoader = function () {
            var _this = this;
            $.each(_this.instance, function (index, el) {
                $(el).find('.' + _this.loader[index].pLoadingClass).remove();
                $(_this.instanceDisplay[index]).fadeIn();
            });
        };

        //Execution
        this.init();
    }

    function GCIFrame(element, config) {
        //Properties
        this.type = 'iframe';
        this.width = $(element).data('gc-width') || config.iframeWidth || 0;
        this.height = $(element).data('gc-height') || config.iframeHeight || 0;
        this.url = $(element).attr('href');
        this.urlImgPoster = $(element).data('gc-poster-image') || ''; //NOT USED
        this.txtImgThumb = config.txtImgThumbIframe || 'IFRAME';
        this.state;
        this.instance = [];
        this.instanceIFrame = [];
        this.dfdState = $.Deferred();
        this.tmp = ['thumb', 'display', 'overlay'];
        this.loader = [];
        this.isMouseEventsOn = false;
        this.thumbnail = $(element).data('gc-thumbnail') || '';

        //Methods
        this.init = function () {
            var _this = this;
            $.each(_this.tmp, function (index, tmpI) {
                _this.loader.push(new GCLoader());
                if (tmpI === 'thumb') {
                    if (_this.thumbnail !== '') {

                        _this.instance.push($('<div class="gc-display-container gc-thumb-img">' +
                                '<img class="gc-display-display" src=' + _this.thumbnail + ' style="width:100%;height:100%;top:0%;left:0%;"/>' +
                                '<div class="gc-icon gc-icon-play"></div>' +
                                '</div>'));
                    } else {
                        _this.instance.push($('<div class="gc-display-container gc-thumb-img">' +
                                '<div class="gc-icon gc-icon-play"></div>' +
                                '<span>' + _this.txtImgThumb + '</span>' +
                                '</div>'));
                    }
//                    _this.instance.push($('<div class="gc-display-container gc-thumb-img">' +
//                            '<div class="gc-icon gc-icon-play"></div>' +
//                            '<span>' + _this.txtImgThumb + '</span>' +
//                            '</div>'));
                    _this.instanceIFrame.push($(''));
                    _this.instance[index].addClass('gc-dd-center');
                }
                if (tmpI === 'display') {
                    _this.instance.push($('<div class="gc-slide-container">' +
                            '<div class="gc-display-container gc-dd-center" style="background-color: rgb(0, 0, 0);">' +
                            '<iframe class="gc-display-display" frameborder="0" allowfullscreen width="100%" height="100%" style = "background: #000;"/>' +
                            '</div>' +
                            '</div>'));
                    _this.instanceIFrame.push(_this.instance[index].find('iframe'));
                    _this.instanceIFrame[index].attr('src', '//about:blank');
                    _this.instance[index].hide();

                    $.when(_this.preload(tmpI)).done(function (result) {
                        _this.fitSize(tmpI);
                        _this.instance[index].fadeIn();
                        _this.dfdState.resolve();
                    });
                }
                if (tmpI === 'overlay') {
                    _this.instance.push($('<div class="gc-overlay-container-display gc-dd-center" style="background-color: rgb(0, 0, 0);">' +
                            '<iframe frameborder="0" allowfullscreen width="100%" height="100%" style = "background: #000;"/>' +
                            '</div>'));
                    _this.instanceIFrame.push(_this.instance[index].find('iframe'));
                    _this.instanceIFrame[index].attr('src', '//about:blank');

                    $.when(_this.preload(tmpI)).done(function (result) {
                        _this.fitSize(tmpI);
                        _this.dfdState.resolve();
                    });
                }
            });
        };
        this.getInstance = function (area, width, height) {
            var _this = this;

            if (area !== 'thumb')
                _this.fitSize(area, width, height);

            return _this.instance[$.inArray(area, _this.tmp)];
        };
        this.preload = function (area) {
            var _this = this;

            return $.Deferred(
                    function (dfd) {
                        _this.instanceIFrame[$.inArray(area, _this.tmp)].on('load', function () {
                            dfd.resolve();
                        }).attr('src', _this.url);
                    }
            ).promise();
        };
        this.fitSize = function (area, cWidth, cHeight) {
            var _this = this;

            var index = $.inArray(area, _this.tmp);

            var oConfig = {};
            if (_this.height <= cHeight &&
                    _this.width <= cWidth) {
                oConfig = {'width': _this.width, 'height': _this.height};
            } else {
                var ratio = _this.width / _this.height;

                if (_this.height > cHeight &&
                        _this.width <= cWidth) {
                    oConfig = {'width': cHeight * ratio, 'height': cHeight};
                }

                if (_this.height < cHeight &&
                        _this.width > cWidth) {
                    oConfig = {'width': cWidth, 'height': cWidth / ratio};
                }

                if (_this.height > cHeight &&
                        _this.width > cWidth) {
                    var ratioC = cWidth / cHeight;
                    if (ratioC < ratio) {
                        if (ratio > 1) {
                            oConfig = {'width': cWidth, 'height': cWidth / ratio};
                        } else {
                            oConfig = {'width': cHeight * ratio, 'height': cHeight};
                        }
                    } else {
                        if (ratio > 1) {
                            oConfig = {'width': cHeight * ratio, 'height': cHeight};
                        } else {
                            oConfig = {'width': cWidth, 'height': cWidth / ratio};
                        }
                    }
                }
            }

            if (area === 'display') {
                _this.instance[index].find('.gc-display-container').css(oConfig);
            } else {
                $(_this.instance[index]).css(oConfig);

            }
        };
        this.addLoader = function () {
            var _this = this;
            $.each(_this.instanceIFrame, function (index, el) {
                if (_this.tmp[index] !== 'thumb')
                    $(el).before(_this.loader[index].getInstance());
            });
        };
        this.removeLoader = function () {
            var _this = this;

            $.each(_this.instance, function (index, el) {
                $(el).find('.' + _this.loader[index].pLoadingClass).remove();
            });
        };
        this.navigate = function (area) {
            var _this = this;
            var index = $.inArray(area, _this.tmp);

            _this.instanceIFrame[index].detach();
            if (area === 'display') {
                _this.instanceIFrame[index].appendTo(_this.instance[index].find('.gc-display-container'));
            }
            if (area === 'overlay') {
                _this.instanceIFrame[index].appendTo(_this.instance[index]);
            }
        };

        //Execution
        this.init();
    }

    function GCVideo(element, config) {
        //Properties
        this.type = 'video';
        this.url = $(element).attr('href');
        this.width = parseInt($(element).data('gc-width'), 10) || config.videoWidth || 0;
        this.height = parseInt($(element).data('gc-height'), 10) || config.videoHeight || 0;
        this.formats = $(element).data('gc-formats') || [];
        this.poster = $(element).data('gc-poster') || '';
        this.txtImgThumb = config.txtImgThumbVideo || 'VIDEO';
        this.instance = [];
        this.instanceVC = []; // video container
        this.instanceVideo = [];
        this.instanceDisplay = [];
        this.state = 'new';
        this.dfdState = $.Deferred();
        this.tmp = ['thumb', 'display', 'overlay'];
        this.isMouseEventsOn = false;
        this.thumbnail = $(element).data('gc-thumbnail') || '';
        console.log('video');

        //Methods
        this.init = function () {
            var _this = this;

            $.each(_this.tmp, function (index, tmpI) {
                if (tmpI === 'thumb') {
                    if (_this.thumbnail !== '') {

                        _this.instance.push($('<div class="gc-display-container">' +
                                '<img class="gc-display-display" src=' + _this.thumbnail + ' style=""/>' +
                                '<div class="gc-icon gc-icon-play"></div>' +
                                '</div>'));
                    } else {
                        _this.instance.push($('<div class="gc-display-container gc-thumb-img">' +
                                '<div class="gc-icon gc-icon-play"></div>' +
                                '<span>' + _this.txtImgThumb + '</span>' +
                                '</div>'));
                    }
//                    _this.instance.push($('<div class="gc-display-container gc-thumb-img">' +
//                                                  '<div class="gc-icon gc-icon-play"></div>' +
//                                                  '<span>' + _this.txtImgThumb + '</span>' +
//                                              '</div>'));
                    _this.instanceVC.push($(''));
                    _this.instanceVideo.push($(''));
                    //_this.instance[index].addClass('gc-dd-center');
                    _this.instanceDisplay.push(_this.instance[index].find('img'));
                }
                if (tmpI === 'display') {
                    _this.instance.push($('<div class="gc-slide-container">' +
                            '<div class="gc-display-container gc-dd-center" style = "background-color: #000;">' +
                            '<video class="gc-display-display" controls preload style="width: 100%; height: 100%;"' +
                            'poster="' + _this.poster + '"/>' +
                            '</div></div>'));
                    _this.instanceVC.push(_this.instance[index].find('.gc-display-container'));
                    _this.instanceVideo.push(_this.instanceVC[index].find('.gc-display-display'));

                    _this.instance[index].hide();
                    _this.formats.unshift(_this.url);
                    $.each(_this.formats, function (i, v) {
                        switch (v.split('.').pop()) {
                            case 'webm':
                                _this.instanceVideo[index].append('<source src="' + v + '" type="video/webm" />');
                                break;
                            case 'ogv':
                                _this.instanceVideo[index].append('<source src="' + v + '" type="video/ogg" />');
                                break;
                            case 'mp4':
                                _this.instanceVideo[index].append('<source src="' + v + '" type="video/mp4" />');
                                break;
                        }
                    });
                    _this.fitSize(tmpI);
                    _this.instance[index].fadeIn();
                    _this.dfdState.resolve();
                }
                if (tmpI === 'overlay') {
                    _this.instance.push($('<div class="gc-overlay-container-display gc-dd-center" style = "background-color: #000;">' +
                            '<video class="gc-overlay-display" controls preload style="width: 100%; height: 100%;"' +
                            'poster="' + _this.poster + '"/>' +
                            '</div>'));
                    _this.instanceVC.push('');
                    _this.instanceVideo.push(_this.instance[index].find('.gc-overlay-display'));

                    _this.instance[index].hide();
                    _this.formats.unshift(_this.url);
                    $.each(_this.formats, function (i, v) {
                        switch (v.split('.').pop()) {
                            case 'webm':
                                _this.instanceVideo[index].append('<source src="' + v + '" type="video/webm" />');
                                break;
                            case 'ogv':
                                _this.instanceVideo[index].append('<source src="' + v + '" type="video/ogg" />');
                                break;
                            case 'mp4':
                                _this.instanceVideo[index].append('<source src="' + v + '" type="video/mp4" />');
                                break;
                        }
                    });
                    _this.instance[index].fadeIn();
                    _this.dfdState.resolve();
                }
            });


        };
        this.getInstance = function (area, width, height) {
            var _this = this;

            if (area !== 'thumb') {
                _this.fitSize(area, width, height);
            }

            return _this.instance[$.inArray(area, _this.tmp)];
        };
        this.fitSize = function (area, cWidth, cHeight) {
            var _this = this;
            var index = $.inArray(area, _this.tmp);
            var oConfig = {};
            console.log(_this);
                $.each(_this.instanceDisplay, function (cindex, el) {
                    if (_this.tmp[cindex] === 'thumb') {
                        console.log(_this.instance[cindex], _this.instance[index].width(), _this.width);
                        var wRatio = _this.instance[cindex].width() / _this.width;
                        var hRatio = _this.instance[cindex].height() / _this.height;
                        var ratioImg = wRatio > hRatio ? wRatio : hRatio;

                        el.width(Math.ceil(_this.width * ratioImg, 10));
                        el.height(Math.ceil(_this.height * ratioImg, 10));

                        var percMarginLeft = ((el.outerWidth() / 2) * 100) / (_this.instance[cindex].outerWidth());
                        var percMarginTop = ((el.outerHeight() / 2) * 100) / (_this.instance[cindex].outerWidth());

                        el.css({
                            'margin-top': "-" + percMarginTop + "%",
                            'margin-left': "-" + percMarginLeft + "%"
                        });
                    }
                });

            if (_this.height <= cHeight &&
                    _this.width <= cWidth) {
                oConfig = {'width': _this.width, 'height': _this.height};
            } else {
                var ratio = _this.width / _this.height;

                if (_this.height > cHeight &&
                        _this.width <= cWidth) {
                    oConfig = {'width': cHeight * ratio, 'height': cHeight};
                }

                if (_this.height < cHeight &&
                        _this.width > cWidth) {
                    oConfig = {'width': cWidth, 'height': cWidth / ratio};
                }

                if (_this.height > cHeight &&
                        _this.width > cWidth) {
                    var ratioC = cWidth / cHeight;
                    if (ratioC < ratio) {
                        if (ratio > 1) {
                            oConfig = {'width': cWidth, 'height': cWidth / ratio};
                        } else {
                            oConfig = {'width': cHeight * ratio, 'height': cHeight};
                        }
                    } else {
                        if (ratio > 1) {
                            oConfig = {'width': cHeight * ratio, 'height': cHeight};
                        } else {
                            oConfig = {'width': cWidth, 'height': cWidth / ratio};
                        }
                    }
                }
            }
            if (area === 'display') {
                _this.instanceVC[index].css(oConfig);
            } else {
                $(_this.instance[index]).css(oConfig);
            }

        };
        this.navigate = function (area) {
            var _this = this;
            var index = $.inArray(area, _this.tmp);

            _this.instanceVideo[index].detach();
            if (area === 'display') {
                _this.instanceVideo[index].appendTo(_this.instanceVC[index]);
            }
            if (area === 'overlay') {
                _this.instanceVideo[index].appendTo(_this.instance[index]);
            }
        };

        //Execution
        this.init();
    }

    function GCDisplay(config, component) {
        //Properties
        this.component = component;
        this.width = config.widthDisplay;
        this.height = config.heightDisplay;
        this.wDperc = 100; //widthDisplayPerc
        this.isShowAlwaysIcons = config.isShowAlwaysIcons;
        this.speedHideIcons = config.speedHideIcons;
        this.mouseEnterCB = config.mouseEnterDisplayCB;
        this.mouseLeaveCB = config.mouseLeaveDisplayCB;
        this.isAutoPlay = config.isAutoPlayDisplay;
        this.pauseTime = config.pauseTimeDisplay;
        this.isPauseOnHover = config.isPauseOnHoverDisplay;
        this.isDownloadEnabled = config.isDownloadEnabled;
        this.downloadPosition = config.downloadPosition;

        this.instancePrime = $('<div class="gc-display-area">' +
                '<div class = "gc-display-area-container">' +
                '<div class="gc-icon gc-icon-download"></div>' +
                '<div class="gc-icon gc-icon-next"></div>' +
                '<div class="gc-icon gc-icon-prev"></div>' +
                '</div>' +
                '</div>');
        this.instance = this.instancePrime.find('.gc-display-area-container');
        this.lens = new GCLens(config, component);
        this.zoom = new GCZoom(config, component);

        if (this.zoom.isZCapEnabled === true) {
            this.zoom.caption = new GCCaption(config, this.zoom);
        }

        //Methods
        this.init = function () {
            var _this = this;

            _this.btnPrevious = _this.instance.find('.gc-icon-prev');
            _this.btnNext = _this.instance.find('.gc-icon-next');
            _this.btnDownload = _this.instance.find('.gc-icon-download');

            if (_this.component.element.find('li').length === 1 || _this.isShowAlwaysIcons === false) {
                _this.btnPrevious.hide();
                _this.btnNext.hide();
                _this.btnDownload.hide();
            }
            _this.currentMousePos = {
                x: -1,
                y: -1
            };
            _this.mousePos = 'out'; //in; out
            _this.isMouseInDspArea = false;
            _this.mouseTimer = 0;
            _this.isTouchMove = false;
            _this.tpStart = {
                x: 0,
                y: 0
            };
            _this.tpEnd = {
                x: 0,
                y: 0
            };
            _this.autoPlayInterval = '';
            _this.supportCanvas = Modernizr.canvas;

            if (_this.isDownloadEnabled === false || _this.supportCanvas === false) {
                _this.btnDownload.addClass('gc-hide');
            } else {
                var cssDownloadPosition = {
                    top: '',
                    bottom: '',
                    right: '',
                    left: ''
                };
                var bW = '-' + _this.instancePrime.css('border-left-width');
                switch (_this.downloadPosition) {
                    case 1:
                        cssDownloadPosition.top = bW;
                        cssDownloadPosition.left = bW;
                        break;
                    case 2:
                        cssDownloadPosition.top = bW;
                        cssDownloadPosition.right = bW;
                        break;
                    case 4:
                        cssDownloadPosition.bottom = bW;
                        cssDownloadPosition.right = bW;
                        break;
                    default:
                        cssDownloadPosition.bottom = bW;
                        cssDownloadPosition.left = bW;
                        break;
                }
                _this.btnDownload.css(cssDownloadPosition);
            }
        };
        this.initEvents = function () {
            var _this = this;

            _this.instance.on('mouseenter.glasscaseA', function () {
                _this.isMouseInDspArea = true;
            }).on('mouseleave.glasscaseA', function () {
                _this.isMouseInDspArea = false;
            });

            $(_this.lens.getInstance()).on('mousemove.glasscase', $.proxy(_this.mousemoveHandler, _this))
                    .on('mouseenter.glasscase', $.proxy(_this.mouseenterHandler, _this))
                    .on('mouseenter.glasscase', $.proxy(_this.mouseEnterCB, _this))
                    .on('mouseleave.glasscase', $.proxy(_this.mouseleaveHandler, _this))
                    .on('mouseleave.glasscase', $.proxy(_this.mouseLeaveCB, _this))
                    .on('touchstart.glasscase', $.proxy(_this.touchStart, _this))
                    .on('touchmove.glasscase', $.proxy(_this.touchMove, _this))
                    .on('touchend.glasscase', $.proxy(_this.touchEnd, _this));

            if (_this.isShowAlwaysIcons === false && _this.component.thumbs.li.length > 1) {
                _this.instance.on('mouseenter.glasscaseDA', $.proxy(_this.toggleNavBtn, _this, 'show', 'mouseenter'))
                        .on('mouseleave.glasscaseDA', $.proxy(_this.toggleNavBtn, _this, 'hide', 'mouseleave'))
                        .on('mousemove.glasscaseDA', function (event) {
                            _this.toggleNavBtn('show');
                            clearTimeout(_this.mouseTimer);
                            _this.mouseTimer = setTimeout(function () {
                                _this.toggleNavBtn('hide');
                            }, _this.speedHideIcons);
                        })
                        .on('touchmove.glasscaseDA', function (event) {
                            _this.toggleNavBtn('show');
                            clearTimeout(_this.mouseTimer);
                            _this.mouseTimer = setTimeout(function () {
                                _this.toggleNavBtn('hide');
                            }, _this.speedHideIcons);

                            if (_this.zoom.isEnabled === true) {
                                event.preventDefault();
                            }
                        });
            }
            if (_this.component.overlay.isEnabled === true) {
                $.each(_this.component.items, function (index, slide) {
                    $.when(slide.dfdstate).done(function () {
                        if (slide.type === 'video') {
                            $(slide.getInstance('display')).on('click.glasscase', function (event) {
                                _this.component.overlay.toggle();
                            }).find('video').on('click.glasscase', function (e) {
                                return false;
                            });
                        } else {
                            $(slide.getInstance('display')).on('click.glasscase', function (event) {
                                _this.component.overlay.toggle();
                            });
                        }
                    });


                });
            }

            _this.btnPrevious.on('click.glasscase', function () {
                _this.navigate('previous');
            });
            _this.btnNext.on('click.glasscase', function () {
                _this.navigate('next');
            });

            _this.btnDownload.on('click.glasscase', function () {
                var canvas = document.createElement('canvas');
                canvas.width = _this.component.items[_this.component.thumbs.current].width;
                canvas.height = _this.component.items[_this.component.thumbs.current].height;
                var context = canvas.getContext('2d');
                var image = _this.instance.find('.gc-display-display')[_this.component.thumbs.current];

                context.drawImage(image, 0, 0);
                var blob = new Blob();
                canvas.toBlob(function (blob) {
                    saveAs(blob, $(image).attr('src').replace(/^.*[\\\/]/, ''));
                }, 'image/png');
            });

            if (_this.isAutoPlay === true) {
                _this.autoPlay('start');

                if (_this.isPauseOnHover === true) {
                    _this.instance.on('mouseenter.glasscase', $.proxy(_this.autoPlay, _this, 'stop'))
                            .on('mouseleave.glasscase', $.proxy(_this.autoPlay, _this, 'start'));
                }
            }
        };
        this.setup = function (wC) {
            var _this = this;

            wC = _this.component.element.outerWidth();
            var nextDW = _this.wDperc * wC / 100;
            var nextDH = nextDW * _this.height / _this.width;

            _this.instancePrime.css({
                'height': '0',
                'width': '0'
            })
                    .css({
                        'height': Math.ceil(nextDH),
                        'width': Math.ceil(nextDW)
                    });

            $.each(_this.component.items, function (index, item) {
                _this.instance.append(_this.component.items[index].getInstance('display', Math.ceil(nextDW), Math.ceil(nextDH)));
            });

            _this.slidesCnt = _this.instance.find('.gc-slide-container');
            $(_this.slidesCnt[_this.component.thumbs.current]).addClass('gc-slide-container-current');
            _this.slides = _this.slidesCnt.find('.gc-display-container');
            _this.display = _this.slides.find('.gc-display-display');

            $.each(_this.component.items, function (index, item) {
                $.when(item.dfdState).done(function () {
                    if (item.type === 'image') {
                        _this.setupDisplay(index, item);
                        _this.setupDisplay(index, item);
                    }
                });
            });

            _this.btnPrevious.css('margin-top', -(_this.btnPrevious.outerHeight() / 2));
            _this.btnNext.css('margin-top', -(_this.btnNext.outerHeight() / 2));
        };
        this.setupZoomLens = function () {
            var _this = this;

            $.when(_this.component.items[_this.component.thumbs.current].dfdState).done(function () {
                if (_this.component.items[_this.component.thumbs.current].isMouseEventsOn === false) {
                    _this.mouseleaveHandler();
                    return;
                }

                if (_this.component.items[_this.component.thumbs.current].type === 'image') {
                    $(_this.slides[_this.component.thumbs.current]).append(_this.lens.getInstance());
                    _this.zoom.changeCnt();

                    $.when(_this.zoom.dfdState).done(function () {
                        _this.lens.setup(_this, _this.component.items[_this.component.thumbs.current]);
                        if (_this.isMousePosInDsp() && _this.isMouseInDspArea === true) {
                            _this.mouseenterHandler();
                        } else {
                            _this.mouseleaveHandler();
                        }
                    });
                }
            });
        };
        this.setupDisplay = function (index, item) {
            var _this = this;
            var ratio;
            var wdd;
            var hdd;

            $(_this.slides[index]).css({
                'width': '0',
                'height': '0'
            })
                    .css({
                        'width': _this.instance.width(),
                        'height': _this.instance.height()
                    });

            item.wDisplayR = $(_this.slides[index]).outerWidth() / item.width;
            item.hDisplayR = $(_this.slides[index]).outerHeight() / item.height;

            if ((item.wDisplayR < 1 || item.hDisplayR < 1)) {
                item.isMouseEventsOn = _this.zoom.isEnabled === true ? true : false;
                ratio = item.wDisplayR < item.hDisplayR ? item.wDisplayR : item.hDisplayR;
            } else {
                // In case that the image's width and height are smaller than the container's width and height
                item.isMouseEventsOn = false;
                ratio = 1;
            }
            wdd = ratio * item.width;
            hdd = ratio * item.height;

            if (_this.component.items[index].type === 'image') {
                $(_this.display[index]).css({
                    'width': wdd,
                    'height': hdd
                });
            }
            $(_this.slides[index]).css({
                'width': wdd,
                'height': hdd
            });

            // Positioning the container in the center of DisplayArea
            var borderVal = parseFloat(_this.instance.css('border-left-width')) * 2;
            var paddingVal = parseFloat(_this.instance.css('padding-top')) * 2;

            var percMarginLeft = (($(_this.slides[index]).outerWidth() / 2) * 100) / (_this.instance.outerWidth() - borderVal - paddingVal);
            var percMarginTop = (($(_this.slides[index]).outerHeight() / 2) * 100) / (_this.instance.outerWidth() - borderVal - paddingVal);

            $(_this.slides[index]).css({
                'margin-left': '-' + percMarginLeft + '%',
                'margin-top': '-' + percMarginTop + '%'
            });
        };
        this.getInstance = function () {
            return this.instancePrime;
        };
        this.mousemoveHandler = function (event, oEventTrigger) {
            var _this = this;

            if (oEventTrigger !== undefined) {
                event = oEventTrigger;
            }

            if (event !== undefined) {
                _this.currentMousePos = _this.isTouchMove === true && event.originalEvent.touches.length === 1 ? {
                    x: event.originalEvent.touches[0].pageX,
                    y: event.originalEvent.touches[0].pageY
                } : {
                    x: event.pageX,
                    y: event.pageY
                };
            }

            if (_this.currentMousePos.x === -1 && _this.currentMousePos.y === -1) {
                return;
            }

            _this.calcMousePos();

            if ((_this.zoom.isSlowZoom === false) || (_this.zoom.isSlowZoom === true && event === undefined)) {
                _this.zoom.display.css({
                    'top': _this.zoom.newZoom.top,
                    'left': _this.zoom.newZoom.left
                });
            }

            if ((_this.lens.isSlowLens === false) || (_this.lens.isSlowLens === true && event === undefined)) {
                _this.lens.display.css({
                    'top': _this.lens.newLens.top,
                    'left': _this.lens.newLens.left
                });
            }
        };
        this.mouseenterHandler = function (event, oEventTrigger) {
            var _this = this;
            if (_this.component.items[_this.component.thumbs.current].isMouseEventsOn === false) {
                return;
            }

            if (oEventTrigger !== undefined) {
                event = oEventTrigger;
            }

            _this.zoom.state = 'showing';

            if (event !== undefined) {
                _this.currentMousePos = _this.isTouchMove === true && event.originalEvent.touches.length === 1 ? {
                    x: event.originalEvent.touches[0].pageX,
                    y: event.originalEvent.touches[0].pageY
                } : {
                    x: event.pageX,
                    y: event.pageY
                };
            }

            _this.calcMousePos();

            _this.zoom.currentZoom = {
                top: _this.zoom.newZoom.top,
                left: _this.zoom.newZoom.left
            };
            _this.zoom.display.css({
                'top': _this.zoom.newZoom.top,
                'left': _this.zoom.newZoom.left
            });

            _this.lens.currentLens = {
                top: _this.lens.newLens.top,
                left: _this.lens.newLens.left
            };
            _this.lens.display.css({
                'top': _this.lens.newLens.top,
                'left': _this.lens.newLens.left
            });

            if (_this.zoom.zooming === false) {
                if (_this.zoom.position === 'inner' || _this.zoom.isAIZooming === true) {
                    _this.zoom.instance.fadeIn(_this.component.config.speed);
                } else {
                    _this.lens.display.fadeIn(_this.component.config.speed);
                    _this.zoom.instance.fadeIn(_this.component.config.speed);
                }
            }

            if (_this.zoom.isSlowZoom === true) {
                clearTimeout(_this.zoom.slowZoomTimer);
                _this.zoom.slowDown();
            }

            if (_this.lens.isSlowLens === true) {
                clearTimeout(_this.lens.slowLensTimer);
                _this.lens.slowDown();
            }
            _this.zoom.zooming = true;
        };
        this.mouseleaveHandler = function (event, oEventTrigger) {
            var _this = this;

            _this.lens.display.stop().hide();
            _this.zoom.instance.stop().fadeOut(_this.component.config.speed);
            _this.zoom.state = 'hiding';

            if (oEventTrigger !== undefined) {
                event = oEventTrigger;
            }

            if (event !== undefined) {
                _this.currentMousePos = _this.isTouchMove === true && event.originalEvent.touches.length === 1 ? {
                    x: event.originalEvent.touches[0].pageX,
                    y: event.originalEvent.touches[0].pageY
                } : {
                    x: event.pageX,
                    y: event.pageY
                };
            }

            if (_this.zoom.isSlowZoom === true) {
                clearTimeout(_this.zoom.slowZoomTimer);
            }

            if (_this.lens.isSlowLens === true) {
                clearTimeout(_this.lens.slowLensTimer);
            }
            _this.zoom.zooming = false;
        };
        this.touchStart = function (event) {

            event.preventDefault();
        };
        this.touchMove = function (event) {
            var _this = this;

            if (_this.isTouchMove === false) {
                _this.isTouchMove = true;
                $(_this.lens.getInstance()).trigger('mouseenter.glasscase', event);
            }
            $(_this.lens.getInstance()).trigger('mousemove.glasscase', event);

            event.preventDefault();
        };
        this.touchEnd = function (event) {
            var _this = this;

            if (_this.isTouchMove === true) {
                $(_this.lens.getInstance()).trigger('mouseleave.glasscase', event);
                _this.isTouchMove = false;
            } else {
                _this.component.overlay.toggle();
            }

            event.preventDefault();
        };
        this.calcMousePos = function () {
            var _this = this;

            var areaDspOffset = $(_this.instance).offset();
            var imgOffset = $(_this.slides[_this.component.thumbs.current]).position();
            var mouseXRelative = _this.currentMousePos.x - (areaDspOffset.left + imgOffset.left + parseFloat($(_this.slides[_this.component.thumbs.current]).css('margin-left')));
            var mouseYRelative = _this.currentMousePos.y - (areaDspOffset.top + imgOffset.top + parseFloat($(_this.slides[_this.component.thumbs.current]).css('margin-top')));
            var imageDisplayHeight = $(_this.display[_this.component.thumbs.current]).outerHeight();
            var imageDisplayWidth = $(_this.display[_this.component.thumbs.current]).outerWidth();
            var lensWidth = _this.lens.display.outerWidth();
            var lensHeight = _this.lens.display.outerHeight();
            var lensTop = mouseYRelative - Math.round(lensHeight / 2);
            var lensLeft = mouseXRelative - Math.round(lensWidth / 2); // 2 -> the middle
            var ratio = _this.component.items[_this.component.thumbs.current].width / imageDisplayWidth;
            var zoomTop = -lensTop * ratio;
            var zoomLeft = -lensLeft * ratio;

            if (mouseYRelative - lensHeight / 2 < 0) {
                lensTop = 0;
                zoomTop = 0;
            }
            if (mouseYRelative + lensHeight / 2 > 0 + imageDisplayHeight) {
                lensTop = imageDisplayHeight - lensHeight;
                zoomTop = -(_this.component.items[_this.component.thumbs.current].height - _this.zoom.instance.outerHeight());
            }
            if (mouseXRelative - lensWidth / 2 < 0) {
                lensLeft = 0;
                zoomLeft = 0;
            }
            if (mouseXRelative + lensWidth / 2 > 0 + imageDisplayWidth) {
                lensLeft = imageDisplayWidth - lensWidth;
                zoomLeft = -(_this.component.items[_this.component.thumbs.current].width - _this.zoom.instance.outerWidth());
            }

            _this.zoom.newZoom = {
                top: zoomTop,
                left: zoomLeft
            };
            _this.lens.newLens = {
                top: lensTop,
                left: lensLeft
            };
        };
        this.isMousePosInDsp = function () {
            var _this = this;

            var areaDspOffset = $(_this.instance).offset();
            var imgOffset = $(_this.slides[_this.component.thumbs.current]).position();
            var mouseXRelative = _this.currentMousePos.x - (areaDspOffset.left + imgOffset.left + parseFloat($(_this.slides[_this.component.thumbs.current]).css('margin-left')));
            var mouseYRelative = _this.currentMousePos.y - (areaDspOffset.top + imgOffset.top + parseFloat($(_this.slides[_this.component.thumbs.current]).css('margin-top')));
            var imageDisplayWidth = $(_this.display[_this.component.thumbs.current]).outerWidth();
            var imageDisplayHeight = $(_this.display[_this.component.thumbs.current]).outerHeight();

            if ((mouseXRelative >= 0 && mouseYRelative >= 0) && (mouseXRelative <= imageDisplayWidth && mouseYRelative <= imageDisplayHeight)) {
                return true;
            } else {
                return false;
            }
        };
        this.navigate = function (direction, context) {
            var _this = context || this;

            _this.component.thumbs.navigate(direction);
            _this.component.changeCnt();
        };
        this.changeCnt = function () {
            var _this = this;
            var currentEl = _this.slidesCnt[_this.component.thumbs.old];
            var nextEl = _this.slidesCnt[_this.component.thumbs.current];
            var dirRight = _this.component.thumbs.old === (_this.component.items.length - 1) && _this.component.thumbs.current === 0;
            var dirLeft = _this.component.thumbs.old === 0 && _this.component.thumbs.current === (_this.component.items.length - 1);
            var dir = ((_this.component.thumbs.old < _this.component.thumbs.current && !dirLeft) || dirRight) ? 'right' : 'left';

            _this.setupZoomLens();
            dynamics.animate(currentEl, {
                opacity: 0
            }, {
                type: dynamics.easeInOut,
                duration: 100,
                complete: function () {
                    dynamics.css(currentEl, {
                        opacity: 0,
                        visibility: 'hidden'
                    });

                    if (_this.component.items[_this.component.thumbs.old].type !== 'image') {
                        _this.component.items[_this.component.thumbs.old].navigate('display');
                    }
                }
            });

            // set the right properties for the next element to come in
            dynamics.css(nextEl, {
                opacity: 1
            });

            _this.slidesCnt.removeClass('gc-slide-container-current');
            $(nextEl).addClass('gc-slide-container-current');

            // animate the next element in
            dynamics.animate(nextEl, {
                opacity: 1
            }, {
                type: dynamics.easeInOut,
                duration: 100,
                complete: function () {
                    dynamics.css(nextEl, {
                        opacity: 1,
                        visibility: 'visible'
                    });
                }
            });
        };
        this.toggleNavBtn = function (action, eventType, event) {
            var _this = this;

            if (event !== undefined) {
                _this.currentMousePos = _this.isTouchMove === true && event.originalEvent.touches.length === 1 ? {
                    x: event.originalEvent.touches[0].pageX,
                    y: event.originalEvent.touches[0].pageY
                } : {
                    x: event.pageX,
                    y: event.pageY
                };
            }

            if (eventType === 'mouseleave') {
                _this.mousePos = 'out';
            }

            if (eventType === 'mouseenter') {
                _this.mousePos = 'in';
            }

            if (action === 'hide') {
                _this.btnPrevious.hide();
                _this.btnNext.hide();
                if (_this.isDownloadEnabled === true) {
                    _this.btnDownload.hide();
                }
            } else {
                _this.btnPrevious.show();
                _this.btnNext.show();
                if (_this.isDownloadEnabled === true) {
                    _this.btnDownload.show();
                }
            }
        };
        this.autoPlay = function (action) {
            var _this = this;

            if (action === 'stop') {
                clearInterval(_this.autoPlayInterval);
            } else {
                _this.autoPlayInterval = setInterval(_this.navigate, _this.pauseTime, 'next', _this);
            }

        };

        //Execution
        this.init();
    }

    function GCLens(config, component) {
        //Properties
        this.component = component;
        this.isSlowLens = config.isSlowLens;
        this.speedSlowLens = config.speedSlowLens;
        this.instance = $('<div class="gc-lens-container">' +
                '<div class="gc-lens-display"></div>' +
                '</div>');
        this.display = this.instance.find('.gc-lens-display');
        this.slowLensTimer = 0;
        this.newLens = {
            left: 0,
            top: 0
        };
        this.currentLens = {
            left: 0,
            top: 0
        };

        //Methods
        this.init = function () {
            var _this = this;

            _this.display.hide();
            _this.newLens = {
                left: 0,
                top: 0
            };
            _this.currentLens = {
                left: 0,
                top: 0
            };
            _this.slowLensTimer = 0;
        };
        this.setup = function (display, slide) {
            var _this = this;

            var percZoomWidth = Math.round(display.zoom.instance.outerWidth() / slide.width * 100);
            //var valueLensW = Math.round(display.display.outerWidth() * percZoomWidth / 100);
            var valueLensW = Math.round($(display.display[_this.component.thumbs.current]).outerWidth() * percZoomWidth / 100);
            var percZoomHeight = Math.round(display.zoom.instance.outerHeight() / slide.height * 100);
            var valueLensH = Math.round($(display.display[_this.component.thumbs.current]).outerHeight() * percZoomHeight / 100);
            var height = $(display.display[_this.component.thumbs.current]).outerHeight();
            var width = $(display.display[_this.component.thumbs.current]).outerWidth();

            _this.instance.css({
                'width': width,
                'height': height
            });
            _this.instance.css({
                'top': $(display.display[_this.component.thumbs.current]).position().top,
                'left': $(display.display[_this.component.thumbs.current]).position().left
            });
            _this.display.css({
                'width': (valueLensW),
                'height': (valueLensH)
            });

            if (display.zoom.position === 'inner' || display.zoom.isAIZooming === true) {
                display.zoom.instance.appendTo(_this.instance);
            }
        };
        this.getInstance = function () {
            return this.instance;
        };
        this.slowDown = function () {
            var _this = this;
            var diffLensPos = {
                left: 0,
                top: 0
            };
            var moveLensPos = {
                left: 0,
                top: 0
            };

            diffLensPos = {
                top: _this.newLens.top - _this.currentLens.top,
                left: _this.newLens.left - _this.currentLens.left
            };
            moveLensPos = {
                top: -diffLensPos.top / (_this.speedSlowLens / 100),
                left: -diffLensPos.left / (_this.speedSlowLens / 100)
            };
            _this.currentLens = {
                top: _this.currentLens.top - moveLensPos.top,
                left: _this.currentLens.left - moveLensPos.left
            };

            if (diffLensPos.top < 1 && diffLensPos.top > -1) {
                _this.currentLens.top = _this.newLens.top;
            }
            if (diffLensPos.left < 1 && diffLensPos.left > -1) {
                _this.currentLens.left = _this.newLens.left;
            }

            _this.display.css({
                'top': _this.currentLens.top,
                'left': _this.currentLens.left
            });
            _this.slowLensTimer = setTimeout(function () {
                _this.slowDown();
            }, 25);
        };

        //Execution
        this.init();
    }

    function GCZoom(config, component) {
        //Properties
        this.component = component;
        this.position = config.zoomPosition;
        this.autoInnerZoom = config.autoInnerZoom;
        this.isEnabled = config.isZoomEnabled;
        this.isSlowZoom = config.isSlowZoom;
        this.speedSlowZoom = config.speedSlowZoom;
        this.isDiffWH = config.isZoomDiffWH;
        this.width = config.zoomWidth;
        this.height = config.zoomHeight;
        this.alignment = config.zoomAlignment;
        this.margin = config.zoomMargin;
        this.isZCapEnabled = config.isZCapEnabled;

        this.instance = $('<div class = "gc-zoom-area"/>');
        this.isAIZooming = false; //for internal use
        this.dfdState = $.Deferred();
        this.state = 'showing'; //showing, hiding

        //Methods
        this.init = function () {
            var _this = this;

            _this.instance.hide();
            _this.container = _this.instance.find('.gc-zoom-container');
            _this.display = _this.container.find('img');

            if (_this.position === 'inner') {
                _this.isDiffWH = true;
                _this.width = 0;
                _this.height = 0;
            }
            _this.zooming = false;
            _this.newZoom = {
                left: 0,
                top: 0
            };
            _this.currentZoom = {
                left: 0,
                top: 0
            };
            _this.slowZoomTimer = 0;
        };
        this.setup = function () {
            var _this = this;

            _this.container.detach();
            _this.instance.append(_this.component.items[_this.component.thumbs.current].getInstance('zoom'));
            _this.container = _this.instance.find('.gc-zoom-container');
            _this.display = _this.container.find('img');

            _this.calcSettings();
        };
        this.calcSettings = function () {
            var _this = this;

            if (_this.position !== 'inner') {
                _this.isAIZooming = false;
                _this.instance.appendTo(_this.component.element).removeClass('gc-zoom-inner');
            }

            if (_this.position === 'inner' || _this.isAIZooming === true) {
                _this.instance.appendTo(_this.component.display.slides[_this.component.thumbs.current]).addClass('gc-zoom-inner');
            }

            var borderVal = parseFloat(_this.instance.css('border-left-width')) * 2 || 0;
            var paddingVal = parseFloat(_this.instance.css('padding-top')) * 2 || 0;
            var wZ = (_this.position === 'inner') ? paddingVal : (borderVal + paddingVal);
            var hZ = (_this.position === 'inner') ? paddingVal : (borderVal + paddingVal);

            for (var i = 0; i < 2; i++) {
                if ((_this.isDiffWH && _this.width > 0)) {
                    wZ += _this.width < _this.component.items[_this.component.thumbs.current].width ? _this.width : _this.component.items[_this.component.thumbs.current].width;
                } else {
                    wZ += $(_this.component.display.display[_this.component.thumbs.current]).outerWidth();
                }
                if ((_this.isDiffWH && _this.height > 0)) {
                    hZ += _this.height < _this.component.items[_this.component.thumbs.current].height ? _this.height : _this.component.items[_this.component.thumbs.current].height;
                } else {
                    hZ += $(_this.component.display.display[_this.component.thumbs.current]).outerHeight();
                }
                if (_this.isDiffWH === false && this.isAIZooming === false) {
                    wZ = hZ;
                }
                if (_this.autoInnerZoom === true && _this.position !== 'inner') {
                    if (_this.component.element.outerWidth() + wZ > $(window).width()) {
                        _this.isAIZooming = true;
                        if (i === 0) {
                            wZ = hZ = paddingVal;
                        }
                    } else {
                        break;
                    }
                } else {
                    break;
                }

                if (_this.position === 'inner') {
                    break;
                }
            }

            _this.container.css({
                'width': 0,
                'height': 0
            });
            _this.instance.css({
                'width': wZ,
                'height': hZ
            });
            _this.container.css({
                'width': _this.instance.outerWidth(),
                'height': _this.instance.outerHeight()
            });

            //setupZoomPos
            if (_this.position === 'inner' || _this.isAIZooming === true) {
                _this.instance.appendTo(_this.component.display.slides[_this.component.thumbs.current]).addClass('gc-zoom-inner');
            } else {
                _this.instance.appendTo(_this.component.element).removeClass('gc-zoom-inner');

                if (_this.position === 'left') {
                    _this.instance.css({
                        'right': (_this.component.element.outerWidth(true)),
                        'margin-right': _this.margin + 'px'
                    });
                } else {
                    _this.instance.css({
                        'left': (_this.component.element.outerWidth(true)),
                        'margin-left': _this.margin + 'px'
                    });
                }

                var topZ = _this.alignment === 'displayArea' ? 0 : $(_this.component.display.slides[_this.component.thumbs.current]).position().top +
                        parseFloat($(_this.component.display.slides[_this.component.thumbs.current]).css('margin-top'));

                if (_this.component.thumbs.position === 'top') {
                    var topT = _this.component.thumbs.instance.outerHeight() + parseFloat(_this.component.thumbs.margin);
                    _this.instance.css({
                        'top': topZ + topT
                    });
                } else {
                    _this.instance.css({
                        'top': topZ
                    });
                }
            }
        };
        this.changeCnt = function () {
            var _this = this;

            _this.setup();
            if (_this.isZCapEnabled === true) {
                _this.caption.setup();
            }

            _this.dfdState.resolve();
        };
        this.getInstance = function () {
            return this.instance;
        };
        this.slowDown = function () {
            var _this = this;
            var diffZoomPos = {
                left: 0,
                top: 0
            };
            var moveZoomPos = {
                left: 0,
                top: 0
            };

            diffZoomPos = {
                top: _this.newZoom.top - _this.currentZoom.top,
                left: _this.newZoom.left - _this.currentZoom.left
            };
            moveZoomPos = {
                top: -diffZoomPos.top / (_this.speedSlowZoom / 100),
                left: -diffZoomPos.left / (_this.speedSlowZoom / 100)
            };
            _this.currentZoom = {
                top: _this.currentZoom.top - moveZoomPos.top,
                left: _this.currentZoom.left - moveZoomPos.left
            };

            if (diffZoomPos.top < 1 && diffZoomPos.top > -1) {
                _this.currentZoom.top = _this.newZoom.top;
            }
            if (diffZoomPos.left < 1 && diffZoomPos.left > -1) {
                _this.currentZoom.left = _this.newZoom.left;
            }
            _this.display.css({
                'top': _this.currentZoom.top,
                'left': _this.currentZoom.left
            });
            _this.slowZoomTimer = setTimeout(function () {
                _this.slowDown();
            }, 25);
        };

        //Execution
        this.init();
    }

    function GCThumbs(config, component) {
        //Properties
        this.component = component;
        this.position = config.thumbsPosition;
        this.nrThumbsPerRow = config.nrThumbsPerRow;
        this.isThumbsOneRow = config.isThumbsOneRow;
        this.isOneThumbShown = config.isOneThumbShown;
        this.firstThumbSelected = config.firstThumbSelected ? config.firstThumbSelected : 0;
        this.colorActiveThumb = config.colorActiveThumb;
        this.margin = config.thumbsMargin;
        this.isHoverShowThumbs = config.isHoverShowThumbs;
        this.slideType = config.slideType;

        //Methods
        this.init = function () {
            var _this = this;

            var sVT = (_this.position === 'right' || _this.position === 'left') ? '-vt' : '';
            var ctntThumbsPrevNext = '<div class="gc-thumbs-area-prev"><div class="gc-icon gc-icon-prev' + sVT + '"></div></div>' +
                    '<div class="gc-thumbs-area-next"><div class="gc-icon gc-icon-next' + sVT + '"></div></div>';

            _this.ul = _this.component.element.find('ul');
            _this.ul.removeClass('gc-start');
            _this.ul.wrap('<div class="gc-thumbs-area"></div>');
            _this.li = _this.ul.find('li');
            _this.instance = _this.ul.parent();
            _this.instance.append(ctntThumbsPrevNext);
            _this.abtnPrevious = _this.instance.find('.gc-thumbs-area-prev');
            _this.btnPrevious = _this.abtnPrevious.find('.gc-icon-prev' + sVT);
            _this.abtnNext = _this.instance.find('.gc-thumbs-area-next');
            _this.btnNext = _this.abtnNext.find('.gc-icon-next' + sVT);
            _this.current = 0; //default value
            _this.old = 0;

            _this.position === 'left' || _this.position === 'right' ? _this.instance.addClass('gc-vt') : _this.instance.addClass('gc-hz');

            if (parseFloat(_this.firstThumbSelected) > -1 &&
                    parseFloat(_this.firstThumbSelected) <= (_this.li.length - 1)) {
                _this.current = _this.firstThumbSelected;
                _this.old = _this.firstThumbSelected;
            }

            _this.currentSlide = Math.floor(_this.current / _this.nrThumbsPerRow);
            _this.currentSlideElement = _this.current;

            _this.tpStart = {
                x: 0,
                y: 0
            };
            _this.tpEnd = {
                x: 0,
                y: 0
            };
        };
        this.setup = function () {
            var _this = this;
            var display = _this.component.display.getInstance();

            if (_this.isOneThumbShown === false && _this.li.length === 1) {
                _this.instance.outerHeight(0);
                _this.instance.addClass('gc-hide');
                return;
            } else {
                _this.isOneThumbShown = true;
            }
            if (_this.position === 'right') {
                _this.setupLR();
                display.css({
                    'top': '0',
                    'left': '0'
                });
                _this.instance.css({
                    'top': '0',
                    'left': display.outerWidth() + _this.margin
                });
            }
            if (_this.position === 'left') {
                _this.setupLR();
                _this.instance.css({
                    'top': '0',
                    'left': '0'
                });
                display.css({
                    'top': '0',
                    'left': _this.instance.outerWidth() + _this.margin
                });
            }
            if (_this.position === 'bottom') {
                _this.setupTB();
                display.css({
                    'top': '0',
                    'left': '0'
                });
                _this.instance.css({
                    'top': display.outerHeight() + _this.margin,
                    'left': '0'
                });
            }
            if (_this.position === 'top') {
                _this.setupTB();
                _this.instance.css({
                    'top': '0',
                    'left': '0'
                });
                display.css({
                    'top': _this.instance.outerHeight() + _this.margin,
                    'left': '0'
                });
            }
            if (parseFloat(_this.firstThumbSelected) > 0 && _this.currentSlide > 0) {
                _this.currentSlide -= 1;
                _this.slide('false', '');
            }
        };
        this.setupLR = function () {
            var _this = this;
            var display = _this.component.display.getInstance();

            _this.instance.css('height', display.outerHeight());

            var mgL = parseFloat(_this.li.css('margin-bottom'));
            var ratio = _this.component.display.width / _this.component.display.height;
            var hL = (_this.instance.outerHeight() / _this.nrThumbsPerRow - (_this.nrThumbsPerRow - 1) * mgL / _this.nrThumbsPerRow);
            var wL = hL * ratio;
            var hLPerc = (hL * 100) / (((hL + mgL) * _this.li.length) - mgL);

            _this.li.css({
                'width': wL,
                'height': hLPerc + '%'
            });
            _this.li.last().css('margin-bottom', 0);

            _this.ul.css({
                'width': Math.ceil(wL),
                'height': Math.ceil((((hL + mgL) * _this.li.length) - mgL))
            });
            _this.instance.css('width', Math.ceil(wL));
            _this.abtnPrevious.removeClass('gc-hide');
            _this.btnPrevious.css('margin-left', (-_this.btnPrevious.outerWidth() / 2));
            _this.abtnNext.removeClass('gc-hide');
            _this.btnNext.css('margin-left', (-_this.btnNext.outerWidth() / 2));

            _this.setupSlider();
            if (_this.component.iOS) {
                var brwLiHeight = _this.li.outerHeight();
                var brwDiff = _this.instance.outerHeight() - (brwLiHeight * _this.nrThumbsPerRow + (_this.nrThumbsPerRow - 1) * mgL);
                _this.ul.find(':nth-child(' + _this.nrThumbsPerRow + 'n)').css('height', brwLiHeight + brwDiff);
            }
        };
        this.setupTB = function () {
            var _this = this;
            var display = _this.component.display.getInstance();

            _this.instance.css('width', display.outerWidth());

            var mgL = parseFloat(_this.li.css('margin-right'));
            var ratio = _this.component.display.width / _this.component.display.height;
            var wL = (_this.instance.outerWidth() / _this.nrThumbsPerRow - (_this.nrThumbsPerRow - 1) * mgL / _this.nrThumbsPerRow);
            var hL = wL / ratio;
            var wLPerc;

            if (_this.isThumbsOneRow === true) {
                wLPerc = (wL * 100) / (((wL + mgL) * _this.li.length) - mgL);
            } else {
                wLPerc = (wL * 100) / _this.instance.outerWidth();
            }
            _this.li.css({
                'width': wLPerc + '%',
                'height': hL
            });

            if (_this.isThumbsOneRow === true) {
                _this.li.last().css('margin-right', 0);
            } else {
                _this.ul.find(':nth-child(' + _this.nrThumbsPerRow + 'n)').css('margin-right', 0);
                _this.ul.find(':nth-child(n +' + (parseFloat(_this.nrThumbsPerRow) + 1) + ')').css('margin-top', mgL + 'px');
            }
            if (_this.isThumbsOneRow === true) {
                _this.ul.css({
                    'width': Math.ceil((wL * _this.li.length + (_this.li.length - 1) * mgL)),
                    'height': Math.ceil(hL)
                });
                _this.instance.css('height', Math.ceil(hL));
            } else {
                var totalRows = Math.ceil((_this.li.length) / _this.nrThumbsPerRow);
                var lHeight = Math.ceil(hL * totalRows + mgL * (totalRows - 1));

                _this.ul.css({
                    'width': _this.instance.outerWidth(),
                    'height': lHeight
                });
                _this.instance.css('height', lHeight);
            }
            if (_this.isThumbsOneRow === true) {
                _this.abtnPrevious.removeClass('gc-hide');
                _this.btnPrevious.css('margin-top', (-_this.btnPrevious.outerHeight() / 2));
                _this.abtnNext.removeClass('gc-hide');
                _this.btnNext.css('margin-top', (-_this.btnNext.outerHeight() / 2));

                _this.setupSlider();
            } else {
                _this.abtnPrevious.addClass('gc-hide');
                _this.abtnNext.addClass('gc-hide');
            }
            if (_this.component.iOS) {
                var brwLiWidth = _this.li.outerWidth(),
                        brwDiff = _this.instance.outerWidth() - (brwLiWidth * _this.nrThumbsPerRow + (_this.nrThumbsPerRow - 1) * mgL);
                _this.ul.find(':nth-child(' + _this.nrThumbsPerRow + 'n)').css('width', brwLiWidth + brwDiff);
            }
        };
        this.setupSlider = function () {
            var _this = this;

            if (_this.li.length <= _this.nrThumbsPerRow) {
                _this.abtnPrevious.addClass('gc-hide');
                _this.abtnNext.addClass('gc-hide');
                return;
            }
            _this.abtnPrevious.removeClass('gc-disabled');
            _this.abtnNext.removeClass('gc-disabled');

            if (_this.slideType !== 'slideElement') {
                if (_this.currentSlide === 0) {
                    _this.abtnPrevious.addClass('gc-disabled');
                }
                if (_this.currentSlide === Math.floor((_this.li.length - 1) / _this.nrThumbsPerRow)) {
                    _this.abtnNext.addClass('gc-disabled');
                }
            }

        };
        this.changeCnt = function () {
            var _this = this;

            if (_this.colorActiveThumb !== -1) {
                _this.ul.find('.gc-active').css('border-color', '');
            }

            _this.li.removeClass('gc-active').eq(_this.current).addClass('gc-active');

            if (_this.colorActiveThumb !== -1) {
                _this.ul.find('.gc-active').css('border-color', _this.colorActiveThumb);
            }
            _this.slide('true', '');
        };
        this.getInstance = function () {
            return this.instance;
        };
        this.initEvents = function () {
            var _this = this;

            _this.li.on('click.glasscase', function () {
                _this.changeThumbs($(this).index());
            });
            if (_this.isHoverShowThumbs === true) {
                _this.li.on('mouseenter', function () {
                    _this.changeThumbs($(this).index());
                });
            }
            _this.abtnPrevious.on('click.glasscase', function () {
                _this.slide('false', 'previous');
            });
            _this.abtnNext.on('click.glasscase', function () {
                _this.slide('false', 'next');
            });
            _this.ul.on('touchstart.glasscase', $.proxy(_this.touchStart, _this))
                    .on('touchmove.glasscase', $.proxy(_this.touchMove, _this))
                    .on('touchend.glasscase', $.proxy(_this.touchEnd, _this));
        };
        this.touchStart = function (event) {
            var _this = this;

            if (event.originalEvent.touches.length === 1) {
                _this.tpStart.x = event.originalEvent.touches[0].pageX;
                _this.tpEnd.x = _this.tpStart.x;
                _this.tpStart.y = event.originalEvent.touches[0].pageY;
                _this.tpEnd.y = _this.tpStart.y;
            }
        };
        this.touchMove = function (event) {
            var _this = this;

            if (event.originalEvent.touches.length === 1) {
                _this.tpEnd.x = event.originalEvent.touches[0].pageX;
                _this.tpEnd.y = event.originalEvent.touches[0].pageY;
            }
        };
        this.touchEnd = function (event) {
            var _this = this;

            if (_this.tpEnd.x !== _this.tpStart.x || _this.tpEnd.y !== _this.tpStart.y) {
                event.preventDefault();

                if (_this.position === 'right' || _this.position === 'left') {
                    if (_this.tpStart.y - _this.tpEnd.y > 10) {
                        _this.slide('false', 'next');
                    }
                    if (_this.tpStart.y - _this.tpEnd.y < -10) {
                        _this.slide('false', 'previous');
                    }
                } else {
                    if (_this.tpStart.x - _this.tpEnd.x > 10) {
                        _this.slide('false', 'next');
                    }
                    if (_this.tpStart.x - _this.tpEnd.x < -10) {
                        _this.slide('false', 'previous');
                    }
                }
            }
        };
        this.slide = function (isImageChange, slideChange) { //isImageChange: true || false; slideChange:   previous || next
            var _this = this;
            var nextSlide = 0;
            var nextSlideElement = 0;
            var vMargin;
            var config;
            var configAnimProp;

            if (_this.isThumbsOneRow === false && (_this.position === 'bottom' || _this.position === 'top')) {
                return;
            }
            config = {
                type: dynamics.easeInOut,
                duration: 800,
                change: function () {
                    _this.setupSlider();
                }
            };
            if (_this.slideType === 'slideElement') {
                if (isImageChange === 'true') {
                    nextSlideElement = _this.current;
                } else {
                    if (slideChange === 'previous') {
                        nextSlideElement = _this.currentSlideElement > 0 ? _this.currentSlideElement - 1 : _this.li.length - 1 /*was 0*/;
                    } else {
                        nextSlideElement = (_this.currentSlideElement + 1) > (_this.li.length - 1) ? 0 : _this.currentSlideElement + 1;
                    }
                }

                _this.currentSlideElement = nextSlideElement;
                //Making the slide
                if (_this.position === 'bottom' || _this.position === 'top') {
                    vMargin = _this.li.outerWidth() + parseFloat(_this.li.css('margin-right'));
                    configAnimProp = {
                        left: -(nextSlideElement * vMargin)
                    };
                } else {
                    vMargin = _this.li.outerHeight() + parseFloat(_this.li.css('margin-bottom'));
                    configAnimProp = {
                        top: -(nextSlideElement * vMargin)
                    };
                }
            } else {
                if (isImageChange === 'true') {
                    nextSlide = Math.floor(_this.current / _this.nrThumbsPerRow);
                } else {
                    if (slideChange === 'previous') {
                        nextSlide = _this.currentSlide > 0 ? _this.currentSlide - 1 : 0;
                    } else {
                        nextSlide = _this.currentSlide + 1 > Math.floor((_this.li.length - 1) / _this.nrThumbsPerRow) ? Math.floor((_this.li.length - 1) / _this.nrThumbsPerRow) : _this.currentSlide + 1;
                    }
                }

                if (nextSlide === _this.currentSlide) {
                    return;
                }

                _this.currentSlide = nextSlide;
                //Making the slide
                if (_this.position === 'bottom' || _this.position === 'top') {
                    vMargin = _this.instance.outerWidth() + parseFloat(_this.li.css('margin-right'));
                    configAnimProp = {
                        translateX: -(nextSlide * vMargin)
                    };
                } else {
                    vMargin = _this.instance.outerHeight() + parseFloat(_this.li.css('margin-bottom'));
                    configAnimProp = {
                        translateY: -(nextSlide * vMargin)
                    };
                }
            }
            dynamics.animate(_this.ul[0], configAnimProp, config);
        };
        this.changeThumbs = function (index) {
            var _this = this;

            if (_this.current !== index) {
                _this.old = _this.current;
                _this.current = index;
                _this.component.changeCnt();
            }
        };
        this.navigate = function (direction) {
            var _this = this;

            _this.old = _this.current;
            if (direction === 'next') {
                _this.current = _this.current === (_this.li.length - 1) ? 0 : _this.current + 1;
            } else {
                _this.current = _this.current === 0 ? (_this.li.length - 1) : _this.current - 1;
            }
        };

        //Execution
        this.init(component);
    }

    function GCOverlay(options, component) {
        //Properties
        this.component = component;
        this.isEnabled = options.isOverlayEnabled;
        this.isFullImage = options.isOverlayFullImage;
        this.isOpened = false;
        this.displayState = ''; //enlarged; compressed
        this.instance = $('<div class="gc-overlay-area">' +
                '<div class="gc-overlay-top-icons">' +
                '<div class="gc-icon gc-icon-close">&nbsp;</div>' +
                '<div class="gc-icon gc-icon-enlarge">&nbsp;</div>' +
                '<div class="gc-icon gc-icon-compress">&nbsp;</div>' +
                '</div>' +
                '<div class="gc-overlay-left-icons">' +
                '<div class="gc-icon gc-icon-prev">&nbsp;</div>' +
                '</div>' +
                '<div class="gc-overlay-right-icons">' +
                '<div class="gc-icon gc-icon-next">&nbsp;</div>' +
                '</div>' +
                '<div class="gc-overlay-gcontainer">' +
                '<div class="gc-overlay-container" />' +
                '</div>' +
                '</div>');

        //Methods
        this.init = function () {
            var _this = this;

            _this.gcontainer = _this.instance.find('.gc-overlay-gcontainer');
            _this.slidesCnt = _this.gcontainer.find('.gc-overlay-container');

            $.each(_this.component.items, function (index, item) {
                _this.slidesCnt.append(_this.component.items[index].getInstance('overlay', _this.slidesCnt.width(), _this.slidesCnt.height()));
            });

            _this.slides = _this.slidesCnt.find('.gc-overlay-container-display');
            _this.display = _this.slides.find('.gc-overlay-display');

            _this.btnPrevious = _this.instance.find('.gc-icon-prev');
            _this.btnNext = _this.instance.find('.gc-icon-next');
            _this.btnClose = _this.instance.find('.gc-icon-close');
            _this.btnEnlarge = _this.instance.find('.gc-icon-enlarge');
            _this.btnCompress = _this.instance.find('.gc-icon-compress');

            _this.tpStart = {
                x: 0,
                y: 0
            };
            _this.tpEnd = {
                x: 0,
                y: 0
            };

            _this.instance.hide();
            _this.btnEnlarge.hide();
            _this.btnCompress.hide();
        };
        this.setup = function () {
            var _this = this;

            $.each(_this.component.items, function (index, item) {
                _this.slidesCnt.append(_this.component.items[index].getInstance('overlay', _this.slidesCnt.width(), _this.slidesCnt.height()));
            });

            if (_this.component.items[_this.component.thumbs.current].type !== 'image') {
                _this.btnCompress.hide();
                _this.btnEnlarge.hide();
                return;
            }

            var isNatSizeSMScr = _this.component.items[_this.component.thumbs.current].isFitScr(_this.instance.outerWidth(), _this.instance.outerHeight());

            if (isNatSizeSMScr || _this.isFullImage) {
                _this.btnCompress.hide();
                _this.btnEnlarge.hide();
                _this.component.items[_this.component.thumbs.current].showNF('nat', $(_this.slides[_this.component.thumbs.current]).outerWidth(), $(_this.slides[_this.component.thumbs.current]).outerHeight());
            } else {
                if (_this.displayState === 'enlarged') { //case when the image is enlarge mode and the browser is resised
                    _this.btnCompress.show();
                    _this.btnEnlarge.hide();
                    _this.component.items[_this.component.thumbs.current].showNF('nat', $(_this.slides[_this.component.thumbs.current]).outerWidth(), $(_this.slides[_this.component.thumbs.current]).outerHeight());
                } else {
                    _this.displayState = 'compressed';
                    _this.btnCompress.hide();
                    _this.btnEnlarge.show();
                    _this.component.items[_this.component.thumbs.current].showNF('fit', $(_this.slides[_this.component.thumbs.current]).outerWidth(), $(_this.slides[_this.component.thumbs.current]).outerHeight());
                }
            }
        };

        this.changeCnt = function (action) {
            var _this = this;
            var currentEl = _this.slides[_this.component.thumbs.old];
            var nextEl = _this.slides[_this.component.thumbs.current];
            var dirRight = _this.component.thumbs.old === (_this.component.items.length - 1) && _this.component.thumbs.current === 0;
            var dirLeft = _this.component.thumbs.old === 0 && _this.component.thumbs.current === (_this.component.items.length - 1);
            var dir = ((_this.component.thumbs.old < _this.component.thumbs.current && !dirLeft) || dirRight) ? 'right' : 'left';

            if (!_this.isOpened) { //overlay on
                return;
            }

            $.when(_this.component.items[_this.component.thumbs.current].dfdState).done(function () {
                _this.setup();
            });

            if (action === 'opening') { //when opening the overlay
                if (_this.component.items[_this.component.thumbs.current].type === 'video' || _this.component.items[_this.component.thumbs.current].type === 'iframe') {
                    _this.component.items[_this.component.thumbs.current].navigate('display');
                }
                dynamics.css(nextEl, {opacity: 0});

                // animate the next element in
                dynamics.animate(nextEl, {
                    opacity: 1
                }, {
                    type: dynamics.easeInOut,
                    duration: 100,
                    complete: function () {

                        dynamics.css(nextEl, {
                            opacity: 1,
                            visibility: 'visible'
                        });

                        _this.slides.removeClass('gc-slide-container-current');
                        $(nextEl).addClass('gc-slide-container-current');
                    }
                });
                return;
                //End opening the overlay
            }

            dynamics.animate(currentEl, {
                opacity: 0
            }, {
                type: dynamics.easeInOut,
                duration: 100,
                complete: function () {
                    dynamics.css(currentEl, {
                        opacity: 0,
                        visibility: 'hidden'
                    });
                    if (_this.component.items[_this.component.thumbs.old].type !== 'image') {
                        _this.component.items[_this.component.thumbs.old].navigate('overlay');
                    }
                }
            });

            // set the right properties for the next element to come in
            dynamics.css(nextEl, {
                opacity: 1,
                visibility: 'visible'
            });
            dynamics.css(nextEl, {
                opacity: 0
            });

            // animate the next element in
            dynamics.animate(nextEl, {
                opacity: 1
            }, {
                type: dynamics.easeInOut,
                duration: 100,
                complete: function () {
                    _this.slides.removeClass('gc-slide-container-current');
                    $(nextEl).addClass('gc-slide-container-current');
                }
            });
        };
        this.getInstance = function () {
            return this.instance;
        };
        this.initEvents = function () {
            var _this = this;

            _this.btnClose.on('click.glasscase', function () {
                _this.toggle();
            });

            _this.slidesCnt.on('click.glasscase', function () {
                _this.toggle();
            });

            if (!_this.isFullImage) {
                $.each(_this.component.items, function (index, slide) {
                    if (slide.type === 'image') {
                        $.when(slide.dfdstate).done(function () {
                            $(slide.getInstance('overlay')).on('dblclick.glasscase', function () {
                                _this.toggleEC('toggle');
                            });
                            $(slide.getInstanceDisplay('overlay')).on('click.glasscase', function (e) {
                                e.stopPropagation();
                            });
                        });
                    }
                });

                _this.btnEnlarge.on('click.glasscase', function () {
                    _this.toggleEC('nat');
                });
                _this.btnCompress.on('click.glasscase', function () {
                    _this.toggleEC('fit');
                });
            }

            _this.btnPrevious.on('click.glasscase', function () {
                _this.navigate('previous');
            });
            _this.btnNext.on('click.glasscase', function () {
                _this.navigate('next');
            });

            if (_this.isFullImage === false) {
                _this.slidesCnt.on('touchstart.glasscase', $.proxy(_this.touchStart, _this))
                        .on('touchmove.glasscase', $.proxy(_this.touchMove, _this))
                        .on('touchend.glasscase', $.proxy(_this.touchEnd, _this));
            }

            $.each(_this.component.items, function (index, slide) {
                if (slide.type === 'image') {
                    $.when(slide.dfdstate).done(function () {
                        $(slide.getInstance('overlay')).on('mousemove.glasscase', function (e) {
                            $(this).scrollTop(e.clientY).scrollLeft(e.clientX);
                        });
                    });
                }
            });
        };
        this.touchStart = function (event) {
            var _this = this;

            if (event.originalEvent.touches.length === 1) {
                _this.tpStart.x = event.originalEvent.touches[0].pageX;
                _this.tpEnd.x = _this.tpStart.x;
                _this.tpStart.y = event.originalEvent.touches[0].pageY;
                _this.tpEnd.y = _this.tpStart.y;
            }
        };
        this.touchMove = function (event) {
            var _this = this;

            if (event.originalEvent.touches.length === 1) {
                _this.tpEnd.x = event.originalEvent.touches[0].pageX;
                _this.tpEnd.y = event.originalEvent.touches[0].pageY;
            }
        };
        this.touchEnd = function (event) {
            var _this = this;

            if (_this.tpEnd.x !== _this.tpStart.x || _this.tpEnd.y !== _this.tpStart.y) {
                event.preventDefault();
                if (_this.tpStart.x - _this.tpEnd.x > 10) {
                    _this.navigate('next');
                }
                if (_this.tpStart.x - _this.tpEnd.x < -10) {
                    _this.navigate('previous');
                }
            }
        };
        this.toggle = function (action) {
            var _this = this;

            if ($('body').hasClass('gc-noscroll')) { //overlay on
                _this.close();
            } else {
                if (action === 'esc') {
                    return;
                }

                _this.instance.fadeIn(500, function () {
                    _this.isOpened = true;
                    _this.changeCnt('opening');
                });
                $('body').addClass('gc-noscroll');
            }
        };
        this.close = function () {
            var _this = this;

            if (_this.component.items[_this.component.thumbs.current].type === 'video' || _this.component.items[_this.component.thumbs.current].type === 'iframe') {
                _this.component.items[_this.component.thumbs.current].navigate('overlay');
            }
            _this.instance.fadeOut(500, function () {
                _this.isOpened = false;
                $('body').removeClass('gc-noscroll');
                _this.slides.removeClass('gc-slide-container-current');
                $(_this.slides).attr('style', '');
            });
        };
        this.toggleEC = function (displayType) {
            var _this = this;

            if (_this.component.items[_this.component.thumbs.current].type !== 'image') {
                return;
            }

            if (displayType === 'toggle' &&
                    _this.component.items[_this.component.thumbs.current].isFitScr(_this.instance.outerWidth(), _this.instance.outerHeight())) {
                return;
            }

            if (displayType === 'toggle') {
                displayType = _this.displayState === 'compressed' ? 'nat' : 'fit';
            }

            if (displayType === 'fit') {
                _this.displayState = 'compressed';
                _this.btnCompress.hide();
                _this.btnEnlarge.show();

                _this.slidesCnt.on('touchstart.glasscase', $.proxy(_this.touchStart, _this))
                        .on('touchmove.glasscase', $.proxy(_this.touchMove, _this))
                        .on('touchend.glasscase', $.proxy(_this.touchEnd, _this));
            }

            if (displayType === 'nat') {
                _this.displayState = 'enlarged';
                _this.btnCompress.show();
                _this.btnEnlarge.hide();

                _this.slidesCnt.off('touchstart.glasscase', $.proxy(_this.touchStart, _this))
                        .off('touchmove.glasscase', $.proxy(_this.touchMove, _this))
                        .off('touchend.glasscase', $.proxy(_this.touchEnd, _this));
            }

            //_this.component.items[_this.component.thumbs.current].showNF(displayType, _this.slides.outerWidth(), _this.slides.outerHeight());
            _this.component.items[_this.component.thumbs.current].showNF(displayType, _this.instance.outerWidth(), _this.instance.outerHeight());
        };
        this.navigate = function (direction) {
            var _this = this;

            _this.component.thumbs.navigate(direction);
            _this.component.changeCnt();
            if (_this.component.items[_this.component.thumbs.current].isFitScr(_this.instance.outerWidth(), _this.instance.outerHeight()) && _this.displayState === 'enlarged') {
                _this.slidesCnt.on('touchstart.glasscase', $.proxy(_this.touchStart, _this))
                        .on('touchmove.glasscase', $.proxy(_this.touchMove, _this))
                        .on('touchend.glasscase', $.proxy(_this.touchEnd, _this));
            }
            if (!_this.component.items[_this.component.thumbs.current].isFitScr(_this.instance.outerWidth(), _this.instance.outerHeight()) && _this.displayState === 'enlarged') {
                _this.slidesCnt.off('touchstart.glasscase', $.proxy(_this.touchStart, _this))
                        .off('touchmove.glasscase', $.proxy(_this.touchMove, _this))
                        .off('touchend.glasscase', $.proxy(_this.touchEnd, _this));
            }
        };
        //Execution
        this.init();
    }

    function GCCaption(options, zoom) {
        //Properties
        this.zoom = zoom;

        this.zType = options.capZType;
        this.zPosition = options.capZPos;
        this.zAlign = options.capZAlign;
        this.instance = $('<div class="gc-caption-container"><div></div></div>');
        this.display = this.instance.find('div');

        //Methods
        this.init = function () {
            var _this = this;
            var cssClass = 'gc-caption-' + _this.zType + _this.zPosition;

            if (_this.zoom.position === 'inner') {
                _this.zType = 'in';
            }

            if ($.inArray(cssClass, ['gc-caption-outtop', 'gc-caption-outbottom', 'gc-caption-intop', 'gc-caption-inbottom']) === -1) {
                cssClass = 'gc-caption-' + _this.zoom.component.defaults.capZType + _this.zoom.component.defaults.capZPos;
            }

            $.inArray(_this.zAlign, ['left', 'right', 'center']) === -1 ?
                    cssClass += ' gc-alignment-' + _this.zoom.component.defaults.capZAlign :
                    cssClass += ' gc-alignment-' + _this.zAlign;

            _this.instance.addClass(cssClass).appendTo(_this.zoom.instance);
        };
        this.setup = function () {
            var _this = this;

            var capTxt = _this.zoom.component.items[_this.zoom.component.thumbs.current].caption;
            capTxt === '' ? _this.instance.hide() : (_this.instance.show(), _this.display.empty().append(capTxt));

            if (_this.zoom.isAIZooming === true) {
                if (_this.zType === 'out') {
                    _this.instance.removeClass('gc-caption-outtop gc-caption-outbottom')
                            .addClass(_this.zPosition === 'top' ? 'gc-caption-intop' : 'gc-caption-inbottom');
                }
            } else {
                if (_this.zType === 'out' && (_this.instance.hasClass('gc-caption-intop') || _this.instance.hasClass('gc-caption-inbottom'))) {
                    _this.instance.removeClass('gc-caption-intop gc-caption-inbottom')
                            .addClass(_this.zPosition === 'top' ? 'gc-caption-outtop' : 'gc-caption-outbottom');
                }
            }
        };
        this.getInstance = function () {
            return this.instance;
        };

        //Execution
        this.init();
    }

    function GlassCase(element, options) {
        var _this = this;

        _this.defaults = {
            //DISPLAY AREA
            widthDisplay: 400, // Default width of the display image
            heightDisplay: 534, // Default height of the display image
            isAutoScaleDisplay: true,
            isAutoScaleHeight: true,
            isDownloadEnabled: false,
            downloadPosition: 3,
            isShowAlwaysIcons: false,
            speedHideIcons: 3000,
            mouseEnterDisplayCB: function () { },
            mouseLeaveDisplayCB: function () { },
            isAutoPlayDisplay: false,
            pauseTimeDisplay: 3000,
            isPauseOnHoverDisplay: true,
            //THUMBS AREA        
            thumbsPosition: 'bottom', // Default position of thumbs. Position is relative to the image display. Can take the values: top; bottom      
            nrThumbsPerRow: 5, // Number of images per row        
            isThumbsOneRow: true, // Show one row or all images: true -> will be shown only one row; false -> will be shown all images
            isOneThumbShown: false,
            firstThumbSelected: 0, // Current element's index
            colorActiveThumb: '-1',
            thumbsMargin: 4, // in px
            isHoverShowThumbs: false,
            slideType: 'slideRow', //slideRow; slideElement
            //ZOOM AREA
            zoomPosition: 'right', // Default position for the zoom. It can take values: right; left; inner
            autoInnerZoom: true, // true; false
            isZoomEnabled: true,
            isSlowZoom: false,
            speedSlowZoom: 1200,
            isZoomDiffWH: false,
            zoomWidth: 0,
            zoomHeight: 0,
            zoomAlignment: 'displayImage', //displayImage, displayArea
            zoomMargin: 4, // in px    
            //LENS AREA
            isSlowLens: false,
            speedSlowLens: 600,
            //OVERLAY AREA
            isOverlayEnabled: true,
            isOverlayFullImage: false,
            //GENERAL
            speed: 400, // Default speed
            isKeypressEnabled: true,
            colorIcons: '-1', // The color of the icons 
            colorLoading: '-1',
            textImageNotLoaded: 'NO IMAGE',
            //CAPTION
            isZCapEnabled: true,
            capZType: 'in', // in, out
            capZPos: 'bottom', // top, bottom
            capZAlign: 'center', // left, center, right
            //VIDEO && IFRAME 
            iframeWidth: 640,
            iframeHeight: 390,
            txtImgThumbIframe: 'IFRAME',
            videoWidth: 640,
            videoHeight: 390,
            txtImgThumbVideo: 'VIDEO'
        };

        _this.element = element.wrap('<div class="glass-case"></div>').parent();
        _this.config = $.extend(true, {}, _this.defaults, options);
        _this.options = options;
        _this.items = [];
        _this.windowWidth = $(window).width();
        _this.process();
    }

    function GCItemFactory() { }
    GCItemFactory.prototype = {
        createItem: function (element, config) {
            switch ($(element).find(':first').data('gc-type')) {
                case 'iframe':
                    GCItemFactory.prototype.itemClass = GCIFrame;
                    break;
                case 'video':
                    GCItemFactory.prototype.itemClass = GCVideo;
                    break;
                case 'image':
                    GCItemFactory.prototype.itemClass = GCImage;
                    break;
                default:
                    GCItemFactory.prototype.itemClass = GCImage;
                    break;
            }

            return new this.itemClass($(element).find(':first'), config);
        }
    };

    GlassCase.prototype = {
        process: function () {
            var _this = this;

            _this.processInput();
            _this.init();

            _this.setup();
            _this.changeCnt();
            _this.initEvents();
        },
        processInput: function () {
            var _this = this;

            var itemFactory = new GCItemFactory();

            _this.element.find('li').each(function () {
                _this.items.push(itemFactory.createItem($(this), _this.config));
            });

            _this.element.find('li').empty();
            $.each(_this.element.find('li'), function (index, li) {
                $(li).append(_this.items[index].getInstance('thumb'));
            });
        },
        init: function () {
            var _this = this;

            _this.display = new GCDisplay(_this.config, _this);
            _this.thumbs = new GCThumbs(_this.config, _this);
            _this.overlay = new GCOverlay(_this.config, _this);

            _this.iOS = $.inArray(window.navigator.platform, ['iPad', 'iPhone', 'iPod']) > -1 ? true : false;
            _this.isTouchMove = false;
            _this.mouseTimer = 0;
            _this.resizeTimer = 0;
            _this.current = 0;
            _this.isFullScreenOn = false;

            if (_this.config.colorIcons !== -1) {
                _this.display.getInstance().find('.gc-icon').css('color', _this.config.colorIcons);
                _this.thumbs.getInstance().find('.gc-icon').css('color', _this.config.colorIcons);
                _this.overlay.getInstance().find('.gc-icon').css('color', _this.config.colorIcons);
            }
        },
        initEvents: function () {
            var _this = this;

            _this.thumbs.initEvents();
            _this.display.initEvents();
            _this.overlay.initEvents();

            $(document).on('fullscreenchange.glasscase', $.proxy(_this.fullScreenHandler, _this))
                    .on('webkitfullscreenchange.glasscase', $.proxy(_this.fullScreenHandler, _this))
                    .on('mozfullscreenchange.glasscase', $.proxy(_this.fullScreenHandler, _this));

            $(document).on('keydown', function (event) {
                if (_this.config.isKeypressEnabled === true) {
                    if (event.keyCode === 37) { //<-
                        _this.display.navigate('previous');
                        _this.thumbs.changeCnt();
                    }

                    if (event.keyCode === 39) { //->
                        _this.display.navigate('next');
                        _this.thumbs.changeCnt();
                    }
                }
                if (event.keyCode === 27) { //esc
                    _this.overlay.close();
                }
            });

            $(window).resize(function () {
                clearTimeout(_this.resizeTimer);
                _this.resizeTimer = setTimeout(function () {
                    _this.resize();
                }, 100);
            });
        },
        setup: function () {
            var _this = this;

            _this.config = $.extend(true, {}, _this.defaults, _this.options);
            _this.thumbs.isOneThumbShown = _this.config.isOneThumbShown;

            var wC; //width component
            var display = _this.display.getInstance();
            if (_this.overlay.isEnabled === true) {
                _this.element.prepend(_this.overlay.instance);
            }
            if (_this.thumbs.position === 'top' || _this.thumbs.position === 'left') {
                _this.element.append(display);
            } else {
                _this.element.prepend(display);
            }
            if ((_this.thumbs.position === 'right' || _this.thumbs.position === 'left') &&
                    (_this.thumbs.isOneThumbShown === false && (_this.thumbs.li.length > 1))) {

                var mgL = parseFloat(_this.thumbs.li.css('margin-bottom'));
                var hL = (parseFloat(_this.config.heightDisplay) - (_this.config.nrThumbsPerRow - 1) * mgL) / _this.config.nrThumbsPerRow;
                var ratio = _this.config.widthDisplay / _this.config.heightDisplay;
                var wL = hL * ratio;
                wC = wL + _this.config.thumbsMargin + parseFloat(_this.config.widthDisplay);

                _this.display.wDperc = Math.round(_this.config.widthDisplay * 100 / wC);

                wC = _this.element.parent().width() > wC ? wC : _this.element.parent().width();

            } else {
                wC = _this.element.parent().width() > _this.config.widthDisplay ? (_this.config.widthDisplay) : _this.element.parent().width();
            }

            _this.element.css({
                'width': wC
            });
            _this.display.setup();
            _this.thumbs.setup();
            _this.overlay.setup();

            if (_this.thumbs.position === 'top' || _this.thumbs.position === 'bottom') {
                var hT = _this.thumbs.isOneThumbShown === false ? 0 : _this.thumbs.instance.outerHeight();
                _this.element.css({
                    'height': hT + display.outerHeight() + parseFloat(_this.thumbs.margin)
                });
            } else {
                var wT = _this.thumbs.isOneThumbShown === false ? 0 : _this.thumbs.instance.outerWidth();
                _this.element.css({
                    'width': wT + display.outerWidth() + parseFloat(_this.thumbs.margin)
                });
                _this.element.css({
                    'height': display.outerHeight()
                });
            }
        },
        changeCnt: function () {
            var _this = this;

            _this.thumbs.changeCnt();
            _this.display.changeCnt();
            _this.overlay.changeCnt();
        },
        resize: function () {
            var _this = this;
            if (_this.isFullScreenOn)
                return;
            //on mobile devices any scrolling is triggering [resize], therefore we keep track of the window.width. If the width is changing => resize
            if (_this.windowWidth === $(window).width()) {
                return;
            }
            _this.windowWidth = $(window).width();

            _this.element.css({
                'height': '0',
                'width': '0'
            });
            _this.setup();
            //Resising the component item, fit size of the thumb images
            $.each(_this.element.find('li'), function (index, li) {
                _this.items[index].fitSize();
            });
        },
        fullScreenHandler: function (event, context) {
            var _this = context || this;

            if (!_this.overlay.isOpened) {
                _this.isFullScreenOn = !_this.isFullScreenOn;
            }
        }
    };
    $.fn.glassCase = function (options) {
        this.each(function () {
            var instance = $.data(this, 'gcglasscase');
            if (!instance) {
                $.data(this, 'gcglasscase', new GlassCase($(this), options));
            }
        });
    };
})(jQuery, window, document);

/*!
 Dynamics.js
 
 Dynamics.js is a JavaScript library to create physics-based animations.
 To see some demos, check out http://dynamicsjs.com.
 */

(function () {
    var t, e, n, r, o, i, s, a, u, l, f, h, p, c, m, d, g, y, v, b, w, x, M, S, k, T, C, H, R, q, I, X, Y, A, j, z, F, G, O, V, Z, E, L, D, P, W, N, U, $, B, K, J, Q, _, te, ee, ne, re, oe = function (t, e) {
        return function () {
            return t.apply(e, arguments)
        }
    };
    I = function () {
        return "visible" === document.visibilityState || null != H.tests
    }, z = function () {
        var t;
        return t = [], "undefined" != typeof document && null !== document && document.addEventListener("visibilitychange", function () {
            var e, n, r, o;
            for (o = [], n = 0, r = t.length; r > n; n++)
                e = t[n], o.push(e(I()));
            return o
        }), function (e) {
            return t.push(e)
        }
    }(), S = function (t) {
        var e, n, r;
        n = {};
        for (e in t)
            r = t[e], n[e] = r;
        return n
    }, x = function (t) {
        var e;
        return e = {}, function () {
            var n, r, o, i, s;
            for (r = "", i = 0, s = arguments.length; s > i; i++)
                n = arguments[i], r += n.toString() + ",";
            return o = e[r], o || (e[r] = o = t.apply(this, arguments)), o
        }
    }, j = function (t) {
        return function (e) {
            var n, r, o;
            return e instanceof Array || e instanceof NodeList || e instanceof HTMLCollection ? o = function () {
                var o, i, s;
                for (s = [], r = o = 0, i = e.length; i >= 0 ? i > o : o > i; r = i >= 0 ? ++o : --o)
                    n = Array.prototype.slice.call(arguments, 1), n.splice(0, 0, e[r]), s.push(t.apply(this, n));
                return s
            }.apply(this, arguments) : t.apply(this, arguments)
        }
    }, y = function (t, e) {
        var n, r, o;
        o = [];
        for (n in e)
            r = e[n], o.push(null != t[n] ? t[n] : t[n] = r);
        return o
    }, v = function (t, e) {
        var n, r, o;
        if (null != t.style)
            return b(t, e);
        o = [];
        for (n in e)
            r = e[n], o.push(t[n] = r.format());
        return o
    }, b = function (t, e) {
        var n, r, o, i, s;
        e = F(e), i = [], n = X(t);
        for (r in e)
            s = e[r], ee.contains(r) ? i.push([r, s]) : (null != s.format && (s = s.format()), "number" == typeof s && (s = "" + s + re(r, s)), null != t.hasAttribute && t.hasAttribute(r) ? t.setAttribute(r, s) : null != t.style && (t.style[O(r)] = s), r in t && (t[r] = s));
        return i.length > 0 ? n ? (o = new l, o.applyProperties(i), t.setAttribute("transform", o.decompose().format())) : (s = i.map(function (t) {
            return ne(t[0], t[1])
        }).join(" "), t.style[O("transform")] = s) : void 0
    }, X = function (t) {
        var e, n;
        return "undefined" != typeof SVGElement && null !== SVGElement && "undefined" != typeof SVGSVGElement && null !== SVGSVGElement ? t instanceof SVGElement && !(t instanceof SVGSVGElement) : null != (e = null != (n = H.tests) && "function" == typeof n.isSVG ? n.isSVG(t) : void 0) ? e : !1
    }, E = function (t, e) {
        var n;
        return n = Math.pow(10, e), Math.round(t * n) / n
    }, f = function () {
        function t(t) {
            var e, n, r;
            for (this.obj = {}, n = 0, r = t.length; r > n; n++)
                e = t[n], this.obj[e] = 1
        }
        return t.prototype.contains = function (t) {
            return 1 === this.obj[t]
        }, t
    }(), te = function (t) {
        return t.replace(/([A-Z])/g, function (t) {
            return "-" + t.toLowerCase()
        })
    }, V = new f("marginTop,marginLeft,marginBottom,marginRight,paddingTop,paddingLeft,paddingBottom,paddingRight,top,left,bottom,right,translateX,translateY,translateZ,perspectiveX,perspectiveY,perspectiveZ,width,height,maxWidth,maxHeight,minWidth,minHeight,borderRadius".split(",")), C = new f("rotate,rotateX,rotateY,rotateZ,skew,skewX,skewY,skewZ".split(",")), ee = new f("translate,translateX,translateY,translateZ,scale,scaleX,scaleY,scaleZ,rotate,rotateX,rotateY,rotateZ,rotateC,rotateCX,rotateCY,skew,skewX,skewY,skewZ,perspective".split(",")), K = new f("accent-height,ascent,azimuth,baseFrequency,baseline-shift,bias,cx,cy,d,diffuseConstant,divisor,dx,dy,elevation,filterRes,fx,fy,gradientTransform,height,k1,k2,k3,k4,kernelMatrix,kernelUnitLength,letter-spacing,limitingConeAngle,markerHeight,markerWidth,numOctaves,order,overline-position,overline-thickness,pathLength,points,pointsAtX,pointsAtY,pointsAtZ,r,radius,rx,ry,seed,specularConstant,specularExponent,stdDeviation,stop-color,stop-opacity,strikethrough-position,strikethrough-thickness,surfaceScale,target,targetX,targetY,transform,underline-position,underline-thickness,viewBox,width,x,x1,x2,y,y1,y2,z".split(",")), re = function (t, e) {
        return "number" != typeof e ? "" : V.contains(t) ? "px" : C.contains(t) ? "deg" : ""
    }, ne = function (t, e) {
        var n, r;
        return n = ("" + e).match(/^([0-9.-]*)([^0-9]*)$/), null != n ? (e = n[1], r = n[2]) : e = parseFloat(e), e = E(parseFloat(e), 10), (null == r || "" === r) && (r = re(t, e)), "" + t + "(" + e + r + ")"
    }, F = function (t) {
        var e, n, r, o, i, s, a, u;
        r = {};
        for (o in t)
            if (i = t[o], ee.contains(o))
                if (n = o.match(/(translate|rotateC|rotate|skew|scale|perspective)(X|Y|Z|)/), n && n[2].length > 0)
                    r[o] = i;
                else
                    for (u = ["X", "Y", "Z"], s = 0, a = u.length; a > s; s++)
                        e = u[s], r[n[1] + e] = i;
            else
                r[o] = i;
        return r
    }, T = function (t) {
        var e;
        return e = "opacity" === t ? 1 : 0, "" + e + re(t, e)
    }, R = function (t, e) {
        var n, r, o, i, s, a, f, h, p, m, d;
        if (i = {}, n = X(t), null != t.style)
            for (s = window.getComputedStyle(t, null), f = 0, p = e.length; p > f; f++)
                r = e[f], ee.contains(r) ? null == i.transform && (o = n ? new l(null != (d = t.transform.baseVal.consolidate()) ? d.matrix : void 0) : u.fromTransform(s[O("transform")]), i.transform = o.decompose()) : (a = null != t.hasAttribute && t.hasAttribute(r) ? t.getAttribute(r) : r in t ? t[r] : s[r], null != a && "d" !== r || !K.contains(r) || (a = t.getAttribute(r)), ("" === a || null == a) && (a = T(r)), i[r] = k(a));
        else
            for (h = 0, m = e.length; m > h; h++)
                r = e[h], i[r] = k(t[r]);
        return c(t, i), i
    }, c = function (t, e) {
        var n, r;
        for (r in e)
            n = e[r], n instanceof i && null != t.style && r in t.style && (n = new a([n, re(r, 0)])), e[r] = n;
        return e
    }, k = function (t) {
        var e, n, o, u, l;
        for (o = [r, s, i, a], u = 0, l = o.length; l > u; u++)
            if (n = o[u], e = n.create(t), null != e)
                return e;
        return null
    }, a = function () {
        function t(t) {
            this.parts = t, this.format = oe(this.format, this), this.interpolate = oe(this.interpolate, this)
        }
        return t.prototype.interpolate = function (e, n) {
            var r, o, i, s, a, u;
            for (s = this.parts, r = e.parts, i = [], o = a = 0, u = Math.min(s.length, r.length); u >= 0 ? u > a : a > u; o = u >= 0 ? ++a : --a)
                i.push(null != s[o].interpolate ? s[o].interpolate(r[o], n) : s[o]);
            return new t(i)
        }, t.prototype.format = function () {
            var t;
            return t = this.parts.map(function (t) {
                return null != t.format ? t.format() : t
            }), t.join("")
        }, t.create = function (e) {
            var n, r, s, a, u, l, f, h, p, c, m;
            for (e = "" + e, s = [], f = [{ re: /(#[a-f\d]{3,6})/gi, klass: o, parse: function (t) { return t } }, { re: /(rgba?\([0-9.]*, ?[0-9.]*, ?[0-9.]*(?:, ?[0-9.]*)?\))/gi, klass: o, parse: function (t) { return t } }, { re: /([-+]?[\d.]+)/gi, klass: i, parse: parseFloat }], h = 0, c = f.length; c > h; h++)
                for (l = f[h], u = l.re; r = u.exec(e); )
                    s.push({index: r.index, length: r[1].length, interpolable: l.klass.create(l.parse(r[1]))});
            for (s = s.sort(function (t, e) { return t.index > e.index ? 1 : - 1 }), a = [], n = 0, p = 0, m = s.length; m > p; p++)
                r = s[p], r.index < n || (r.index > n && a.push(e.substring(n, r.index)), a.push(r.interpolable), n = r.index + r.length);
            return n < e.length && a.push(e.substring(n)), new t(a)
        }, t
    }(), s = function () {
        function t(t) {
            this.format = oe(this.format, this), this.interpolate = oe(this.interpolate, this), this.obj = t
        }
        return t.prototype.interpolate = function (e, n) {
            var r, o, i, s, a;
            s = this.obj, r = e.obj, i = {};
            for (o in s)
                a = s[o], i[o] = null != a.interpolate ? a.interpolate(r[o], n) : a;
            return new t(i)
        }, t.prototype.format = function () {
            return this.obj
        }, t.create = function (e) {
            var n, r, o;
            if (e instanceof Object) {
                r = {};
                for (n in e)
                    o = e[n], r[n] = k(o);
                return new t(r)
            }
            return null
        }, t
    }(), i = function () {
        function t(t) {
            this.format = oe(this.format, this), this.interpolate = oe(this.interpolate, this), this.value = parseFloat(t)
        }
        return t.prototype.interpolate = function (e, n) {
            var r, o;
            return o = this.value, r = e.value, new t((r - o) * n + o)
        }, t.prototype.format = function () {
            return E(this.value, 5)
        }, t.create = function (e) {
            return "number" == typeof e ? new t(e) : null
        }, t
    }(), r = function () {
        function t(t) {
            this.values = t, this.format = oe(this.format, this), this.interpolate = oe(this.interpolate, this)
        }
        return t.prototype.interpolate = function (e, n) {
            var r, o, i, s, a, u;
            for (s = this.values, r = e.values, i = [], o = a = 0, u = Math.min(s.length, r.length); u >= 0 ? u > a : a > u; o = u >= 0 ? ++a : --a)
                i.push(null != s[o].interpolate ? s[o].interpolate(r[o], n) : s[o]);
            return new t(i)
        }, t.prototype.format = function () {
            return this.values.map(function (t) {
                return null != t.format ? t.format() : t
            })
        }, t.createFromArray = function (e) {
            var n;
            return n = e.map(function (t) {
                return k(t) || t
            }), n = n.filter(function (t) {
                return null != t
            }), new t(n)
        }, t.create = function (e) {
            return e instanceof Array ? t.createFromArray(e) : null
        }, t
    }(), t = function () {
        function t(t, e) {
            this.rgb = null != t ? t : {}, this.format = e, this.toRgba = oe(this.toRgba, this), this.toRgb = oe(this.toRgb, this), this.toHex = oe(this.toHex, this)
        }
        return t.fromHex = function (e) {
            var n, r;
            return n = e.match(/^#([a-f\d]{1})([a-f\d]{1})([a-f\d]{1})$/i), null != n && (e = "#" + n[1] + n[1] + n[2] + n[2] + n[3] + n[3]), r = e.match(/^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i), null != r ? new t({r: parseInt(r[1], 16), g: parseInt(r[2], 16), b: parseInt(r[3], 16), a: 1}, "hex") : null
        }, t.fromRgb = function (e) {
            var n, r;
            return n = e.match(/^rgba?\(([0-9.]*), ?([0-9.]*), ?([0-9.]*)(?:, ?([0-9.]*))?\)$/), null != n ? new t({r: parseFloat(n[1]), g: parseFloat(n[2]), b: parseFloat(n[3]), a: parseFloat(null != (r = n[4]) ? r : 1)}, null != n[4] ? "rgba" : "rgb") : null
        }, t.componentToHex = function (t) {
            var e;
            return e = t.toString(16), 1 === e.length ? "0" + e : e
        }, t.prototype.toHex = function () {
            return "#" + t.componentToHex(this.rgb.r) + t.componentToHex(this.rgb.g) + t.componentToHex(this.rgb.b)
        }, t.prototype.toRgb = function () {
            return "rgb(" + this.rgb.r + ", " + this.rgb.g + ", " + this.rgb.b + ")"
        }, t.prototype.toRgba = function () {
            return "rgba(" + this.rgb.r + ", " + this.rgb.g + ", " + this.rgb.b + ", " + this.rgb.a + ")"
        }, t
    }(), o = function () {
        function e(t) {
            this.color = t, this.format = oe(this.format, this), this.interpolate = oe(this.interpolate, this)
        }
        return e.prototype.interpolate = function (n, r) {
            var o, i, s, a, u, l, f, h;
            for (a = this.color, o = n.color, s = {}, h = ["r", "g", "b"], l = 0, f = h.length; f > l; l++)
                i = h[l], u = Math.round((o.rgb[i] - a.rgb[i]) * r + a.rgb[i]), s[i] = Math.min(255, Math.max(0, u));
            return i = "a", u = E((o.rgb[i] - a.rgb[i]) * r + a.rgb[i], 5), s[i] = Math.min(1, Math.max(0, u)), new e(new t(s, o.format))
        }, e.prototype.format = function () {
            return "hex" === this.color.format ? this.color.toHex() : "rgb" === this.color.format ? this.color.toRgb() : "rgba" === this.color.format ? this.color.toRgba() : void 0
        }, e.create = function (n) {
            var r;
            if ("string" == typeof n)
                return r = t.fromHex(n) || t.fromRgb(n), null != r ? new e(r) : null
        }, e
    }(), n = function () {
        function t(t) {
            this.props = t, this.applyRotateCenter = oe(this.applyRotateCenter, this), this.format = oe(this.format, this), this.interpolate = oe(this.interpolate, this)
        }
        return t.prototype.interpolate = function (e, n) {
            var r, o, i, s, a, u, l, f, h, p, c, m;
            for (i = {}, p = ["translate", "scale", "rotate"], s = 0, f = p.length; f > s; s++)
                for (o = p[s], i[o] = [], r = a = 0, c = this.props[o].length; c >= 0 ? c > a : a > c; r = c >= 0 ? ++a : --a)
                    i[o][r] = (e.props[o][r] - this.props[o][r]) * n + this.props[o][r];
            for (r = u = 1; 2 >= u; r = ++u)
                i.rotate[r] = e.props.rotate[r];
            for (m = ["skew"], l = 0, h = m.length; h > l; l++)
                o = m[l], i[o] = (e.props[o] - this.props[o]) * n + this.props[o];
            return new t(i)
        }, t.prototype.format = function () {
            return "translate(" + this.props.translate.join(",") + ") rotate(" + this.props.rotate.join(",") + ") skewX(" + this.props.skew + ") scale(" + this.props.scale.join(",") + ")"
        }, t.prototype.applyRotateCenter = function (t) {
            var e, n, r, o, i, s;
            for (n = w.createSVGMatrix(), n = n.translate(t[0], t[1]), n = n.rotate(this.props.rotate[0]), n = n.translate( - t[0], - t[1]), r = new l(n), o = r.decompose().props.translate, s = [], e = i = 0; 1 >= i; e = ++i)
                s.push(this.props.translate[e] -= o[e]);
            return s
        }, t
    }(), w = "undefined" != typeof document && null !== document ? document.createElementNS("http://www.w3.org/2000/svg", "svg") : void 0, l = function () {
        function t(t) {
            this.m = t, this.applyProperties = oe(this.applyProperties, this), this.decompose = oe(this.decompose, this), this.m || (this.m = w.createSVGMatrix())
        }
        return t.prototype.decompose = function () {
            var t, e, r, o, i;
            return o = new h([this.m.a, this.m.b]), i = new h([this.m.c, this.m.d]), t = o.length(), r = o.dot(i), o = o.normalize(), e = i.combine(o, 1, -r).length(), new n({translate: [this.m.e, this.m.f], rotate: [180 * Math.atan2(this.m.b, this.m.a) / Math.PI, this.rotateCX, this.rotateCY], scale: [t, e], skew: r / e * 180 / Math.PI})
        }, t.prototype.applyProperties = function (t) {
            var e, n, r, o, i, s, a, u;
            for (e = {}, i = 0, s = t.length; s > i; i++)
                r = t[i], e[r[0]] = r[1];
            for (n in e)
                o = e[n], "translateX" === n ? this.m = this.m.translate(o, 0) : "translateY" === n ? this.m = this.m.translate(0, o) : "scaleX" === n ? this.m = this.m.scaleNonUniform(o, 1) : "scaleY" === n ? this.m = this.m.scaleNonUniform(1, o) : "rotateZ" === n ? this.m = this.m.rotate(o) : "skewX" === n ? this.m = this.m.skewX(o) : "skewY" === n && (this.m = this.m.skewY(o));
            return this.rotateCX = null != (a = e.rotateCX) ? a : 0, this.rotateCY = null != (u = e.rotateCY) ? u : 0
        }, t
    }(), h = function () {
        function t(t) {
            this.els = t, this.combine = oe(this.combine, this), this.normalize = oe(this.normalize, this), this.length = oe(this.length, this), this.cross = oe(this.cross, this), this.dot = oe(this.dot, this), this.e = oe(this.e, this)
        }
        return t.prototype.e = function (t) {
            return 1 > t || t > this.els.length ? null : this.els[t - 1]
        }, t.prototype.dot = function (t) {
            var e, n, r;
            if (e = t.els || t, r = 0, n = this.els.length, n !== e.length)
                return null;
            for (n += 1; --n; )
                r += this.els[n - 1] * e[n - 1];
            return r
        }, t.prototype.cross = function (e) {
            var n, r;
            return r = e.els || e, 3 !== this.els.length || 3 !== r.length ? null : (n = this.els, new t([n[1] * r[2] - n[2] * r[1], n[2] * r[0] - n[0] * r[2], n[0] * r[1] - n[1] * r[0]]))
        }, t.prototype.length = function () {
            var t, e, n, r, o;
            for (t = 0, o = this.els, n = 0, r = o.length; r > n; n++)
                e = o[n], t += Math.pow(e, 2);
            return Math.sqrt(t)
        }, t.prototype.normalize = function () {
            var e, n, r, o, i;
            r = this.length(), o = [], i = this.els;
            for (n in i)
                e = i[n], o[n] = e / r;
            return new t(o)
        }, t.prototype.combine = function (e, n, r) {
            var o, i, s, a;
            for (i = [], o = s = 0, a = this.els.length; a >= 0 ? a > s : s > a; o = a >= 0 ? ++s : --s)
                i[o] = n * this.els[o] + r * e.els[o];
            return new t(i)
        }, t
    }(), e = function () {
        function t() {
            this.toMatrix = oe(this.toMatrix, this), this.format = oe(this.format, this), this.interpolate = oe(this.interpolate, this)
        }
        return t.prototype.interpolate = function (e, n, r) {
            var o, i, s, a, u, l, f, h, p, c, m, d, g, y, v, b, w, x;
            for (null == r && (r = null), s = this, i = new t, w = ["translate", "scale", "skew", "perspective"], d = 0, b = w.length; b > d; d++)
                for (f = w[d], i[f] = [], a = g = 0, x = s[f].length - 1; x >= 0 ? x >= g : g >= x; a = x >= 0 ? ++g : --g)
                    i[f][a] = null == r || r.indexOf(f) > -1 || r.indexOf("" + f + ["x", "y", "z"][a]) > -1 ? (e[f][a] - s[f][a]) * n + s[f][a] : s[f][a];
            if (null == r || -1 !== r.indexOf("rotate")) {
                if (h = s.quaternion, p = e.quaternion, o = h[0] * p[0] + h[1] * p[1] + h[2] * p[2] + h[3] * p[3], 0 > o) {
                    for (a = y = 0; 3 >= y; a = ++y)
                        h[a] = -h[a];
                    o = -o
                }
                for (o + 1 > .05 ? 1 - o >= .05 ? (m = Math.acos(o), l = 1 / Math.sin(m), c = Math.sin(m * (1 - n)) * l, u = Math.sin(m * n) * l) : (c = 1 - n, u = n) : (p[0] = - h[1], p[1] = h[0], p[2] = - h[3], p[3] = h[2], c = Math.sin(piDouble * (.5 - n)), u = Math.sin(piDouble * n)), i.quaternion = [], a = v = 0; 3 >= v; a = ++v)
                    i.quaternion[a] = h[a] * c + p[a] * u
            } else
                i.quaternion = s.quaternion;
            return i
        }, t.prototype.format = function () {
            return this.toMatrix().toString()
        }, t.prototype.toMatrix = function () {
            var t, e, n, r, o, i, s, a, l, f, h, p, c, m, d, g;
            for (t = this, o = u.I(4), e = c = 0; 3 >= c; e = ++c)
                o.els[e][3] = t.perspective[e];
            for (i = t.quaternion, f = i[0], h = i[1], p = i[2], l = i[3], s = t.skew, r = [[1, 0], [2, 0], [2, 1]], e = m = 2; m >= 0; e = --m)
                s[e] && (a = u.I(4), a.els[r[e][0]][r[e][1]] = s[e], o = o.multiply(a));
            for (o = o.multiply(new u([[1 - 2 * (h * h + p * p), 2 * (f * h - p * l), 2 * (f * p + h * l), 0], [2 * (f * h + p * l), 1 - 2 * (f * f + p * p), 2 * (h * p - f * l), 0], [2 * (f * p - h * l), 2 * (h * p + f * l), 1 - 2 * (f * f + h * h), 0], [0, 0, 0, 1]])), e = d = 0; 2 >= d; e = ++d) {
                for (n = g = 0; 2 >= g; n = ++g)
                    o.els[e][n] *= t.scale[e];
                o.els[3][e] = t.translate[e]
            }
            return o
        }, t
    }(), u = function () {
        function t(t) {
            this.els = t, this.toString = oe(this.toString, this), this.decompose = oe(this.decompose, this), this.inverse = oe(this.inverse, this), this.augment = oe(this.augment, this), this.toRightTriangular = oe(this.toRightTriangular, this), this.transpose = oe(this.transpose, this), this.multiply = oe(this.multiply, this), this.dup = oe(this.dup, this), this.e = oe(this.e, this)
        }
        return t.prototype.e = function (t, e) {
            return 1 > t || t > this.els.length || 1 > e || e > this.els[0].length ? null : this.els[t - 1][e - 1]
        }, t.prototype.dup = function () {
            return new t(this.els)
        }, t.prototype.multiply = function (e) {
            var n, r, o, i, s, a, u, l, f, h, p, c, m;
            for (c = e.modulus ? !0 : !1, n = e.els || e, "undefined" == typeof n[0][0] && (n = new t(n).els), h = this.els.length, u = h, l = n[0].length, o = this.els[0].length, i = [], h += 1; --h; )
                for (s = u - h, i[s] = [], p = l, p += 1; --p; ) {
                    for (a = l - p, m = 0, f = o, f += 1; --f; )
                        r = o - f, m += this.els[s][r] * n[r][a];
                    i[s][a] = m
                }
            return n = new t(i), c ? n.col(1) : n
        }, t.prototype.transpose = function () {
            var e, n, r, o, i, s, a;
            for (a = this.els.length, e = this.els[0].length, n = [], i = e, i += 1; --i; )
                for (r = e - i, n[r] = [], s = a, s += 1; --s; )
                    o = a - s, n[r][o] = this.els[o][r];
            return new t(n)
        }, t.prototype.toRightTriangular = function () {
            var t, e, n, r, o, i, s, a, u, l, f, h, p, c;
            for (t = this.dup(), a = this.els.length, o = a, i = this.els[0].length; --a; ) {
                if (n = o - a, 0 === t.els[n][n])
                    for (r = f = p = n + 1; o >= p ? o > f : f > o; r = o >= p ? ++f : --f)
                        if (0 !== t.els[r][n]) {
                            for (e = [], u = i, u += 1; --u; )
                                l = i - u, e.push(t.els[n][l] + t.els[r][l]);
                            t.els[n] = e;
                            break
                        }
                if (0 !== t.els[n][n])
                    for (r = h = c = n + 1; o >= c ? o > h : h > o; r = o >= c ? ++h : --h) {
                        for (s = t.els[r][n] / t.els[n][n], e = [], u = i, u += 1; --u; )
                            l = i - u, e.push(n >= l ? 0 : t.els[r][l] - t.els[n][l] * s);
                        t.els[r] = e
                    }
            }
            return t
        }, t.prototype.augment = function (e) {
            var n, r, o, i, s, a, u, l, f;
            if (n = e.els || e, "undefined" == typeof n[0][0] && (n = new t(n).els), r = this.dup(), o = r.els[0].length, l = r.els.length, a = l, u = n[0].length, l !== n.length)
                return null;
            for (l += 1; --l; )
                for (i = a - l, f = u, f += 1; --f; )
                    s = u - f, r.els[i][o + s] = n[i][s];
            return r
        }, t.prototype.inverse = function () {
            var e, n, r, o, i, s, a, u, l, f, h, p, c;
            for (f = this.els.length, a = f, e = this.augment(t.I(f)).toRightTriangular(), u = e.els[0].length, i = [], f += 1; --f; ) {
                for (o = f - 1, r = [], h = u, i[o] = [], n = e.els[o][o], h += 1; --h; )
                    p = u - h, l = e.els[o][p] / n, r.push(l), p >= a && i[o].push(l);
                for (e.els[o] = r, s = c = 0; o >= 0 ? o > c : c > o; s = o >= 0 ? ++c : --c) {
                    for (r = [], h = u, h += 1; --h; )
                        p = u - h, r.push(e.els[s][p] - e.els[o][p] * e.els[s][o]);
                    e.els[s] = r
                }
            }
            return new t(i)
        }, t.I = function (e) {
            var n, r, o, i, s;
            for (n = [], i = e, e += 1; --e; )
                for (r = i - e, n[r] = [], s = i, s += 1; --s; )
                    o = i - s, n[r][o] = r === o ? 1 : 0;
            return new t(n)
        }, t.prototype.decompose = function () {
            var t, n, r, o, i, s, a, u, l, f, p, c, m, d, g, y, v, b, w, x, M, S, k, T, C, H, R, q, I, X, Y, A, j, z, F, G, O, V;
            for (s = this, x = [], v = [], b = [], f = [], u = [], t = [], n = I = 0; 3 >= I; n = ++I)
                for (t[n] = [], o = X = 0; 3 >= X; o = ++X)
                    t[n][o] = s.els[n][o];
            if (0 === t[3][3])
                return !1;
            for (n = Y = 0; 3 >= Y; n = ++Y)
                for (o = A = 0; 3 >= A; o = ++A)
                    t[n][o] /= t[3][3];
            for (l = s.dup(), n = j = 0; 2 >= j; n = ++j)
                l.els[n][3] = 0;
            if (l.els[3][3] = 1, 0 !== t[0][3] || 0 !== t[1][3] || 0 !== t[2][3]) {
                for (c = new h(t.slice(0, 4)[3]), r = l.inverse(), M = r.transpose(), u = M.multiply(c).els, n = z = 0; 2 >= z; n = ++z)
                    t[n][3] = 0;
                t[3][3] = 1
            } else
                u = [0, 0, 0, 1];
            for (n = F = 0; 2 >= F; n = ++F)
                x[n] = t[3][n], t[3][n] = 0;
            for (d = [], n = G = 0; 2 >= G; n = ++G)
                d[n] = new h(t[n].slice(0, 3));
            if (v[0] = d[0].length(), d[0] = d[0].normalize(), b[0] = d[0].dot(d[1]), d[1] = d[1].combine(d[0], 1, -b[0]), v[1] = d[1].length(), d[1] = d[1].normalize(), b[0] /= v[1], b[1] = d[0].dot(d[2]), d[2] = d[2].combine(d[0], 1, -b[1]), b[2] = d[1].dot(d[2]), d[2] = d[2].combine(d[1], 1, -b[2]), v[2] = d[2].length(), d[2] = d[2].normalize(), b[1] /= v[2], b[2] /= v[2], a = d[1].cross(d[2]), d[0].dot(a) < 0)
                for (n = O = 0; 2 >= O; n = ++O)
                    for (v[n] *= - 1, o = V = 0; 2 >= V; o = ++V)
                        d[n].els[o] *= -1;
            g = function (t, e) {
                return d[t].els[e]
            }, m = [], m[1] = Math.asin(-g(0, 2)), 0 !== Math.cos(m[1]) ? (m[0] = Math.atan2(g(1, 2), g(2, 2)), m[2] = Math.atan2(g(0, 1), g(0, 0))) : (m[0] = Math.atan2(-g(2, 0), g(1, 1)), m[1] = 0), w = g(0, 0) + g(1, 1) + g(2, 2) + 1, w > 1e-4 ? (y = .5 / Math.sqrt(w), C = .25 / y, H = (g(2, 1) - g(1, 2)) * y, R = (g(0, 2) - g(2, 0)) * y, q = (g(1, 0) - g(0, 1)) * y) : g(0, 0) > g(1, 1) && g(0, 0) > g(2, 2) ? (y = 2 * Math.sqrt(1 + g(0, 0) - g(1, 1) - g(2, 2)), H = .25 * y, R = (g(0, 1) + g(1, 0)) / y, q = (g(0, 2) + g(2, 0)) / y, C = (g(2, 1) - g(1, 2)) / y) : g(1, 1) > g(2, 2) ? (y = 2 * Math.sqrt(1 + g(1, 1) - g(0, 0) - g(2, 2)), H = (g(0, 1) + g(1, 0)) / y, R = .25 * y, q = (g(1, 2) + g(2, 1)) / y, C = (g(0, 2) - g(2, 0)) / y) : (y = 2 * Math.sqrt(1 + g(2, 2) - g(0, 0) - g(1, 1)), H = (g(0, 2) + g(2, 0)) / y, R = (g(1, 2) + g(2, 1)) / y, q = .25 * y, C = (g(1, 0) - g(0, 1)) / y), f = [H, R, q, C], p = new e, p.translate = x, p.scale = v, p.skew = b, p.quaternion = f, p.perspective = u, p.rotate = m;
            for (k in p) {
                S = p[k];
                for (i in S)
                    T = S[i], isNaN(T) && (S[i] = 0)
            }
            return p
        }, t.prototype.toString = function () {
            var t, e, n, r, o;
            for (n = "matrix3d(", t = r = 0; 3 >= r; t = ++r)
                for (e = o = 0; 3 >= o; e = ++o)
                    n += E(this.els[t][e], 10), (3 !== t || 3 !== e) && (n += ",");
            return n += ")"
        }, t.matrixForTransform = x(function (t) {
            var e, n, r, o, i, s;
            return e = document.createElement("div"), e.style.position = "absolute", e.style.visibility = "hidden", e.style[O("transform")] = t, document.body.appendChild(e), r = window.getComputedStyle(e, null), n = null != (o = null != (i = r.transform) ? i : r[O("transform")]) ? o : null != (s = H.tests) ? s.matrixForTransform(t) : void 0, document.body.removeChild(e), n
        }), t.fromTransform = function (e) {
            var n, r, o, i, s, a;
            for (i = null != e ? e.match(/matrix3?d?\(([-0-9,e \.]*)\)/) : void 0, i ? (n = i[1].split(","), n = n.map(parseFloat), r = 6 === n.length ? [n[0], n[1], 0, 0, n[2], n[3], 0, 0, 0, 0, 1, 0, n[4], n[5], 0, 1] : n) : r = [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1], s = [], o = a = 0; 3 >= a; o = ++a)
                s.push(r.slice(4 * o, 4 * o + 4));
            return new t(s)
        }, t
    }(), G = x(function (t) {
        var e, n, r, o, i, s, a, u, l, f;
        if (void 0 !== document.body.style[t])
            return "";
        for (o = t.split("-"), i = "", s = 0, u = o.length; u > s; s++)
            r = o[s], i += r.substring(0, 1).toUpperCase() + r.substring(1);
        for (f = ["Webkit", "Moz", "ms"], a = 0, l = f.length; l > a; a++)
            if (n = f[a], e = n + i, void 0 !== document.body.style[e])
                return n;
        return ""
    }), O = x(function (t) {
        var e;
        return e = G(t), "Moz" === e ? "" + e + (t.substring(0, 1).toUpperCase() + t.substring(1)) : "" !== e ? "-" + e.toLowerCase() + "-" + te(t) : te(t)
    }), Z = "undefined" != typeof window && null !== window ? window.requestAnimationFrame : void 0, d = [], g = [], N = !1, U = 1, "undefined" != typeof window && null !== window && window.addEventListener("keyup", function (t) {
        return 68 === t.keyCode && t.shiftKey && t.ctrlKey ? H.toggleSlow() : void 0
    }), null == Z && (Y = 0, Z = function (t) {
        var e, n, r;
        return e = Date.now(), r = Math.max(0, 16 - (e - Y)), n = window.setTimeout(function () {
            return t(e + r)
        }, r), Y = e + r, n
    }), D = !1, L = !1, B = function () {
        return D ? void 0 : (D = !0, Z(P))
    }, P = function (t) {
        var e, n, r, o;
        if (L)
            return void Z(P);
        for (n = [], r = 0, o = d.length; o > r; r++)
            e = d[r], m(t, e) || n.push(e);
        return d = d.filter(function (t) {
            return -1 === n.indexOf(t)
        }), 0 === d.length ? D = !1 : Z(P)
    }, m = function (t, e) {
        var n, r, o, i, s, a, u, l;
        if (null == e.tStart && (e.tStart = t), i = (t - e.tStart) / e.options.duration, s = e.curve(i), r = {}, i >= 1)
            r = e.curve.returnsToSelf ? e.properties.start : e.properties.end;
        else {
            l = e.properties.start;
            for (n in l)
                o = l[n], r[n] = q(o, e.properties.end[n], s)
        }
        return v(e.el, r), "function" == typeof (a = e.options).change && a.change(e.el, Math.min(1, i)), i >= 1 && "function" == typeof (u = e.options).complete && u.complete(e.el), 1 > i
    }, q = function (t, e, n) {
        return null != t && null != t.interpolate ? t.interpolate(e, n) : null
    }, $ = function (t, e, n, r) {
        var o, i, s, a, f, h, p;
        if (null != r && (g = g.filter(function (t) {
            return t.id !== r
        })), H.stop(t, {timeout: !1}), !n.animated)
            return H.css(t, e), void ("function" == typeof n.complete && n.complete(this));
        f = R(t, Object.keys(e)), e = F(e), o = {}, h = [];
        for (s in e)
            p = e[s], null != t.style && ee.contains(s) ? h.push([s, p]) : o[s] = k(p);
        return h.length > 0 && (i = X(t), i ? (a = new l, a.applyProperties(h)) : (p = h.map(function (t) {
            return ne(t[0], t[1])
        }).join(" "), a = u.fromTransform(u.matrixForTransform(p))), o.transform = a.decompose(), i && f.transform.applyRotateCenter([o.transform.props.rotate[1], o.transform.props.rotate[2]])), c(t, o), d.push({el: t, properties: {start: f, end: o}, options: n, curve: n.type.call(n.type, n)}), B()
    }, _ = [], Q = 0, W = function (t) {
        return I() ? Z(function () {
            return -1 !== _.indexOf(t) ? t.realTimeoutId = setTimeout(function () {
                return t.fn(), M(t.id)
            }, t.delay) : void 0
        }) : void 0
    }, p = function (t, e) {
        var n;
        return Q += 1, n = {id: Q, tStart: Date.now(), fn: t, delay: e, originalDelay: e}, W(n), _.push(n), Q
    }, M = function (t) {
        return _ = _.filter(function (e) {
            return e.id === t && e.realTimeoutId && clearTimeout(e.realTimeoutId), e.id !== t
        })
    }, A = function (t, e) {
        var n;
        return null != t ? (n = t - e.tStart, e.originalDelay - n) : e.originalDelay
    }, "undefined" != typeof window && null !== window && window.addEventListener("unload", function () { }), J = null, z(function (t) {
        var e, n, r, o, i, s, a, u, l, f;
        if (L = !t, t) {
            if (D)
                for (n = Date.now() - J, i = 0, u = d.length; u > i; i++)
                    e = d[i], null != e.tStart && (e.tStart += n);
            for (s = 0, l = _.length; l > s; s++)
                r = _[s], r.delay = A(J, r), W(r);
            return J = null
        }
        for (J = Date.now(), f = [], o = 0, a = _.length; a > o; o++)
            r = _[o], f.push(clearTimeout(r.realTimeoutId));
        return f
    }), H = {}, H.linear = function () {
        return function (t) {
            return t
        }
    }, H.spring = function (t) {
        var e, n, r, o, i, s;
        return null == t && (t = {}), y(t, H.spring.defaults), o = Math.max(1, t.frequency / 20), i = Math.pow(20, t.friction / 100), s = t.anticipationSize / 1e3, r = Math.max(0, s), e = function (e) {
            var n, r, o, i, a;
            return n = .8, i = s / (1 - s), a = 0, o = (i - n * a) / (i - a), r = (n - o) / i, r * e * t.anticipationStrength / 100 + o
        }, n = function (t) {
            return Math.pow(i / 10, -t) * (1 - t)
        }, function (t) {
            var r, i, a, u, l, f, h, p;
            return f = t / (1 - s) - s / (1 - s), s > t ? (p = s / (1 - s) - s / (1 - s), h = 0 / (1 - s) - s / (1 - s), l = Math.acos(1 / e(p)), a = (Math.acos(1 / e(h)) - l) / (o * -s), r = e) : (r = n, l = 0, a = 1), i = r(f), u = o * (t - s) * a + l, 1 - i * Math.cos(u)
        }
    }, H.bounce = function (t) {
        var e, n, r, o;
        return null == t && (t = {}), y(t, H.bounce.defaults), r = Math.max(1, t.frequency / 20), o = Math.pow(20, t.friction / 100), e = function (t) {
            return Math.pow(o / 10, -t) * (1 - t)
        }, n = function (t) {
            var n, o, i, s;
            return s = -1.57, o = 1, n = e(t), i = r * t * o + s, n * Math.cos(i)
        }, n.returnsToSelf = !0, n
    }, H.gravity = function (t) {
        var e, n, r, o, i, s, a;
        return null == t && (t = {}), y(t, H.gravity.defaults), n = Math.min(t.bounciness / 1250, .8), o = t.elasticity / 1e3, a = 100, r = [], e = function () {
            var r, o;
            for (r = Math.sqrt(2 / a), o = { a: - r, b: r, H: 1 }, t.returnsToSelf && (o.a = 0, o.b = 2 * o.b); o.H > .001; )
                e = o.b - o.a, o = {a: o.b, b: o.b + e * n, H: o.H * n * n};
            return o.b
        }(), s = function (n, r, o, i) {
            var s, a;
            return e = r - n, a = 2 / e * i - 1 - 2 * n / e, s = a * a * o - o + 1, t.returnsToSelf && (s = 1 - s), s
        }, function () {
            var i, s, u, l;
            for (s = Math.sqrt(2 / (a * e * e)), u = { a: - s, b: s, H: 1 }, t.returnsToSelf && (u.a = 0, u.b = 2 * u.b), r.push(u), i = e, l = []; u.b < 1 && u.H > .001; )
                i = u.b - u.a, u = {a: u.b, b: u.b + i * n, H: u.H * o}, l.push(r.push(u));
            return l
        }(), i = function (e) {
            var n, o, i;
            for (o = 0, n = r[o]; !(e >= n.a && e <= n.b) && (o += 1, n = r[o]); )
                ;
            return i = n ? s(n.a, n.b, n.H, e) : t.returnsToSelf ? 0 : 1
        }, i.returnsToSelf = t.returnsToSelf, i
    }, H.forceWithGravity = function (t) {
        return null == t && (t = {}), y(t, H.forceWithGravity.defaults), t.returnsToSelf = !0, H.gravity(t)
    }, H.bezier = function () {
        var t, e, n;
        return e = function (t, e, n, r, o) {
            return Math.pow(1 - t, 3) * e + 3 * Math.pow(1 - t, 2) * t * n + 3 * (1 - t) * Math.pow(t, 2) * r + Math.pow(t, 3) * o
        }, t = function (t, n, r, o, i) {
            return {x: e(t, n.x, r.x, o.x, i.x), y: e(t, n.y, r.y, o.y, i.y)}
        }, n = function (t, e, n) {
            var r, o, i, s, a, u, l, f, h, p;
            for (r = null, h = 0, p = e.length; p > h && (o = e[h], t >= o(0).x && t <= o(1).x && (r = o), null === r); h++)
                ;
            if (!r)
                return n ? 0 : 1;
            for (f = 1e-4, s = 0, u = 1, a = (u + s) / 2, l = r(a).x, i = 0; Math.abs(t - l) > f && 100 > i; )
                t > l ? s = a : u = a, a = (u + s) / 2, l = r(a).x, i += 1;
            return r(a).y
        }, function (e) {
            var r, o, i;
            return null == e && (e = {}), i = e.points, r = function () {
                var e, n, o;
                r = [], o = function (e, n) {
                    var o;
                    return o = function (r) {
                        return t(r, e, e.cp[e.cp.length - 1], n.cp[0], n)
                    }, r.push(o)
                };
                for (e in i) {
                    if (n = parseInt(e), n >= i.length - 1)
                        break;
                    o(i[n], i[n + 1])
                }
                return r
            }(), o = function (t) {
                return 0 === t ? 0 : 1 === t ? 1 : n(t, r, this.returnsToSelf)
            }, o.returnsToSelf = 0 === i[i.length - 1].y, o
        }
    }(), H.easeInOut = function (t) {
        var e, n;
        return null == t && (t = {}), e = null != (n = t.friction) ? n : H.easeInOut.defaults.friction, H.bezier({points: [{x: 0, y: 0, cp: [{x: .92 - e / 1e3, y: 0}]}, {x: 1, y: 1, cp: [{x: .08 + e / 1e3, y: 1}]}]})
    }, H.easeIn = function (t) {
        var e, n;
        return null == t && (t = {}), e = null != (n = t.friction) ? n : H.easeIn.defaults.friction, H.bezier({points: [{x: 0, y: 0, cp: [{x: .92 - e / 1e3, y: 0}]}, {x: 1, y: 1, cp: [{x: 1, y: 1}]}]})
    }, H.easeOut = function (t) {
        var e, n;
        return null == t && (t = {}), e = null != (n = t.friction) ? n : H.easeOut.defaults.friction, H.bezier({points: [{x: 0, y: 0, cp: [{x: 0, y: 0}]}, {x: 1, y: 1, cp: [{x: .08 + e / 1e3, y: 1}]}]})
    }, H.spring.defaults = {frequency: 300, friction: 200, anticipationSize: 0, anticipationStrength: 0}, H.bounce.defaults = {frequency: 300, friction: 200}, H.forceWithGravity.defaults = H.gravity.defaults = {bounciness: 400, elasticity: 200}, H.easeInOut.defaults = H.easeIn.defaults = H.easeOut.defaults = {friction: 500}, H.css = j(function (t, e) {
        return b(t, e, !0)
    }), H.animate = j(function (t, e, n) {
        var r;
        return null == n && (n = {}), n = S(n), y(n, {type: H.easeInOut, duration: 1e3, delay: 0, animated: !0}), n.duration = Math.max(0, n.duration * U), n.delay = Math.max(0, n.delay), 0 === n.delay ? $(t, e, n) : (r = H.setTimeout(function () {
            return $(t, e, n, r)
        }, n.delay), g.push({id: r, el: t}))
    }), H.stop = j(function (t, e) {
        return null == e && (e = {}), null == e.timeout && (e.timeout = !0), e.timeout && (g = g.filter(function (n) {
            return n.el !== t || null != e.filter && !e.filter(n) ? !0 : (H.clearTimeout(n.id), !1)
        })), d = d.filter(function (e) {
            return e.el !== t
        })
    }), H.setTimeout = function (t, e) {
        return p(t, e * U)
    }, H.clearTimeout = function (t) {
        return M(t)
    }, H.toggleSlow = function () {
        return N = !N, U = N ? 3 : 1, "undefined" != typeof console && null !== console && "function" == typeof console.log ? console.log("dynamics.js: slow animations " + (N ? "enabled" : "disabled")) : void 0
    }, "object" == typeof module && "object" == typeof module.exports ? module.exports = H : "function" == typeof define ? define("dynamics", function () {
        return H
    }) : window.dynamics = H
}).call(this);
