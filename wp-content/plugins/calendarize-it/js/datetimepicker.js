(function( $ ) {
	var xdsoftDatetimepicker = $.fn.datetimepicker;

	$.fn.extend({
		xdsoft_datetimepicker: function( options ) {
			return xdsoftDatetimepicker.call( this, options );
		}
	});
})( jQuery );

function rhcDateTimePicker( dateFormat, timeFormat, locale ) {
	that = this;

	this.dateFormatter = new DateFormatter();
	this.dateFormat = ( dateFormat ) ? formatFilter( dateFormat ) : formatFilter( RHCDTP.date_format );
	this.timeFormat = ( timeFormat ) ? formatFilter( timeFormat ) : formatFilter( RHCDTP.time_format );
	this.locale = ( locale ) ? locale : RHCDTP.locale;
	this.dayOfWeekStart = RHCDTP.cal_firstday;

	jQuery.datetimepicker.setLocale( this.locale );
	
	var timeStep = +RHCDTP.time_step || 60;

	var dateOptions = {
		timepicker: false,
		scrollMonth: false,
		scrollInput: false,
		format: this.dateFormat,
		todayButton: false,
		validateOnBlur: false,
		dayOfWeekStart: this.dayOfWeekStart,
		onSelectDate: function( currentTime, $input ) {
			$input.data( 'date-object', currentTime );
		}
	};

	var timeOptions = {
		datepicker: false,
		scrollTime: false,
		format: this.timeFormat,
		formatTime: this.timeFormat,
		validateOnBlur: false,
		step: timeStep,
		onSelectTime: function( currentTime, $input ) {
			$input.data( 'date-object', currentTime );
		}
	};

	this.initDatePicker = function( $elements ) {
		this.initDateTimePikcer( $elements, dateOptions );
	}

	this.initTimePicker = function( $elements ) {
		var config = {
			type: 'time',
		};
		this.initDateTimePikcer( $elements, timeOptions, config );
	}

	this.initDatePickerStart = function( $group, elements ) {
		var dateOptionsStart = jQuery.extend( {}, dateOptions );
		
		dateOptionsStart.onShow = function( currentTime ) {
			var maxDate = that.getElementDateObject( $group.find( elements.endDate ) );

			this.setOptions({
				maxDate: maxDate ? maxDate : false
			});
		};

		dateOptionsStart.onSelectDate = function( currentTime, $input ) {
			var theTimeObject = that.getElementDateObject( $group.find( elements.startTime ) );

			if ( theTimeObject ) {
				var endDateObject = that.getElementDateObject( $group.find( elements.endDate ) );
				var endTimeObject = that.getElementDateObject( $group.find( elements.endTime ) );

				if ( endDateObject && endTimeObject ) {
					var startDate = setTimeToDateObject( currentTime, theTimeObject );
					var endDate = setTimeToDateObject( endDateObject, endTimeObject );
					
					if ( startDate >= endDate ) {
						that.setElementDateObject( $group.find( elements.startTime ), '' );
						$group.find( elements.startTime ).val( '' );
					}
				}
			}

			that.setElementDateObject( $input, currentTime );
		}

		this.initDateTimePikcer( $group.find( elements.startDate ), dateOptionsStart );
	}

	this.initDatePickerEnd = function( $group, elements ) {
		var dateOptionsEnd = jQuery.extend( {}, dateOptions );

		dateOptionsEnd.onShow = function() {
			var minDate = that.getElementDateObject( $group.find( elements.startDate ) );

			this.setOptions({
				minDate: minDate ? minDate : false
			});
		};

		dateOptionsEnd.onSelectDate = function( currentTime, $input ) {
			var theTimeObject = that.getElementDateObject( $group.find( elements.endTime ) );

			if ( theTimeObject ) {
				var startDateObject = that.getElementDateObject( $group.find( elements.startDate ) );
				var startTimeObject = that.getElementDateObject( $group.find( elements.startTime ) );
				
				if ( startDateObject && startTimeObject ) {
					var startDate = setTimeToDateObject( startDateObject, startTimeObject );
					var endDate = setTimeToDateObject( currentTime, theTimeObject );
					
					if ( startDate >= endDate ) {
						that.setElementDateObject( $group.find( elements.endTime ), '' );
						$group.find( elements.endTime ).val( '' );
					}
				}
			}

			that.setElementDateObject( $input, currentTime );
		}

		this.initDateTimePikcer( $group.find( elements.endDate ), dateOptionsEnd );
	}

	this.initTimePickerStart = function( $group, elements ) {
		var timeOptionsStart = jQuery.extend( {}, timeOptions );
		timeOptionsStart.onShow = function() {
			var endDate = that.getElementDateObject( $group.find( elements.endDate ) );
			var endTime = that.getElementDateObject( $group.find( elements.endTime ) );
			var defaultDate = that.getElementDateObject( $group.find( elements.startDate ) );

			if ( endDate && endTime ) {
				var maxTime = setTimeToDateObject( endDate, endTime );
			}

			this.setOptions({
				defaultDate: defaultDate ? defaultDate : false,
				maxTime: maxTime ? maxTime : false,
			});
		};

		var config = {
			type: 'time',
			group: $group,
			elements: elements
		};
		this.initDateTimePikcer( $group.find( elements.startTime ), timeOptionsStart, config );
	}

	this.initTimePickerEnd = function( $group, elements ) {
		var timeOptionsEnd = jQuery.extend( {}, timeOptions );
		timeOptionsEnd.onShow = function() {
			var startDate = that.getElementDateObject( $group.find( elements.startDate ) );
			var startTime = that.getElementDateObject( $group.find( elements.startTime ) );
			var defaultDate = that.getElementDateObject( $group.find( elements.endDate ) );

			if ( startDate && startTime ) {
				var minTime = startDate;
				minTime.setHours( startTime.getHours() );
				minTime.setMinutes( startTime.getMinutes() + 1 );	
			}
			
			this.setOptions({
				defaultDate: defaultDate ? defaultDate : false,
				minTime: minTime ? minTime : false,
			});
		};

		var config = {
			type: 'time',
			group: $group,
			elements: elements
		};
		this.initDateTimePikcer( $group.find( elements.endTime ), timeOptionsEnd, config );
	}

	this.formatDate = function( date, format ) {
		return this.dateFormatter.formatDate( date, format );
	}

	this.parseDate = function( date, format ) {
		return this.dateFormatter.parseDate( date, format );
	}

	this.initDateTimePikcer = function( $el, pickerOptions, config ) {
		$el.xdsoft_datetimepicker( pickerOptions ).on( 'focus', function() {
			jQuery( this ).trigger( 'blur' );
		});
		
		if ( config ) {
			if ( 'time' === config.type ) {
				$el.unbind( 'focus' ).on( 'change', function() {
					var $this = jQuery( this );
					
					if ( '' === $this.val() ) {
						that.setElementDateObject( $this, '' );

						return false;
					}

					var previousTimeObject = that.getElementDateObject( $this );
					var isRightDate = false;
					var theTimeObject = that.getTimeObject( $this.val(), that.timeFormat );

					if ( theTimeObject ) {
						isRightDate = true;

						if ( config.elements ) {
							var startDate = that.getElementDateObject( config.group.find( config.elements.startDate ) );
							var endDate = that.getElementDateObject( config.group.find( config.elements.endDate ) );

							if ( startDate && endDate ) {
								var args = {
									startDate: startDate,
									endDate: endDate,
								};

								if ( $this.is( config.elements.startTime ) ) {
									var endTime = that.getElementDateObject( config.group.find( config.elements.endTime ) );
									if ( endTime ) {
										args.startTime = theTimeObject;
										args.endTime = endTime;
									}
								} else if ( $this.is( config.elements.endTime ) ) {
									var startTime = that.getElementDateObject( config.group.find( config.elements.startTime ) );
									if ( startTime ) {
										args.startTime = startTime;
										args.endTime = theTimeObject;
									}
								}

								if ( args.startTime && args.endTime ) {
									if ( ! isStartDateBeforeEndDate( args ) ) {
										isRightDate = false;
									}
								}
							}
						}
					}

					if ( isRightDate ) {
						that.setElementDateObject( $this, theTimeObject );
						$this.val( that.dateFormatter.formatDate( theTimeObject, that.timeFormat ) );
					} else {
						$this.val( that.dateFormatter.formatDate( previousTimeObject, that.timeFormat ) );
					}
				});
			}
		} else {
			$el.on( 'keydown copy cut paste', function( event ) {
				if ( event.keyCode == 9 || event.shitKey || event.altKey || event.ctrlKey || ( event.ctrlKey && event.keyCode == 82 ) ) return true;
				event.preventDefault();

				return false;
			})
		}
	}

	this.getTimeObject = function( time, format ) {
		format = format || this.timeFormat;
		format = format.replace( /\\\\./g, ' ' ).replace( /\s\s+/g, ' ' ).trim();

		var timeString = getTimeString( time );

		if ( ! timeString ) {
			return false;
		}
		
		return this.dateFormatter.parseDate( timeString, format );
	}

	this.getElementDateObject = function( $element ) {
		return $element.data( 'date-object' );
	}

	this.setElementDateObject = function( $element, value ) {
		return $element.data( 'date-object', value );
	}

	function formatFilter( format ) {
		return format.replace( /\\/g, '\\\\' );
	}

	function isStartDateBeforeEndDate( elements ) {
		elements.startDate.setHours( elements.startTime.getHours() );
		elements.startDate.setMinutes( elements.startTime.getMinutes() );
		elements.endDate.setHours( elements.endTime.getHours() );
		elements.endDate.setMinutes( elements.endTime.getMinutes() );

		return ( elements.startDate < elements.endDate );
	}

	function getTimeString( time ) {
		var timeNumbers = time.replace( /[^\d]/g, '' ).replace( /\s+/g, '' ).trim();
		var meridiem = time.match( /am|pm/i );

		if ( timeNumbers.length < 1 || ( meridiem && timeNumbers.length < 3 ) ) {
			return false;
		}

		timeNumbers = ( timeNumbers.length % 2 === 0 || timeNumbers.length > 3 ) ? timeNumbers : '0' + timeNumbers;
		timeNumbers = timeNumbers.match( /\d{2}/g );
		timeNumbers = ( timeNumbers.length < 2 ) ? timeNumbers[0] : timeNumbers[0] + ':' + timeNumbers[1];
	
		return ( meridiem ) ? timeNumbers + ' ' + meridiem : timeNumbers;
	}

	function setTimeToDateObject( dateObject, timeObject ) {
		dateObject.setHours( timeObject.getHours() );
		dateObject.setMinutes( timeObject.getMinutes() );

		return dateObject;
	}
}