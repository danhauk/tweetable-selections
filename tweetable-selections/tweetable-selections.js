var tweetable_selection_width = 100;

// get highlighted text
function get_selection_text() {
    var text = "";

    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
}

// get coordinates of highlighted text
function get_selection_coords() {
    var sel = document.selection, range, rect;
    var x = 0, y = 0;
    if (sel) {
        if (sel.type != "Control") {
            range = sel.createRange();
            range.collapse(true);
            x = range.boundingLeft;
            y = range.boundingTop;
            x2 = rect.boundingRight;
        }
    } else if (window.getSelection) {
        sel = window.getSelection();
        if (sel.rangeCount) {
            range = sel.getRangeAt(0).cloneRange();
            if (range.getClientRects) {
                rect = range.getClientRects()[0];
                x = rect.left;
                y = rect.top;
                x2 = rect.right;
            }
            // Fall back to inserting a temporary element
            if (x == 0 && y == 0) {
                var span = document.createElement("span");
                if (span.getClientRects) {
                    // Ensure span has dimensions and position by
                    // adding a zero-width space character
                    span.appendChild( document.createTextNode("\u200b") );
                    range.insertNode(span);
                    rect = span.getClientRects()[0];
                    x = rect.left;
                    y = rect.top;
                    x2 = rect.right;
                    var spanParent = span.parentNode;
                    spanParent.removeChild(span);

                    // Glue any broken text nodes back together
                    spanParent.normalize();
                }
            }
        }
    }
    return { x: x, x2: x2, y: y };
}

// show share div if highlighted text is not null
function show_tweetable_selection_div() {
	var selected_text = get_selection_text();
	var d = document.getElementById( 'tweetable-selection' );

	if ( selected_text != '' ) {
        var tweet_link = document.getElementById( 'tweetable-selection--twitter' );
        var text_quote = encodeURIComponent( '"' + selected_text + '"' );

        // add selected text to onclick event
        var tweet_url = tweet_link.getAttribute( 'onclick' );
        tweet_url = tweet_url.split( '\');' );

        if ( tweet_url[0].indexOf( '&text=' ) > -1 ) {
            tweet_url[0] = tweet_url[0].substring( 0, tweet_url[0].indexOf( '&text=' ) );
        }
        
        tweet_url = tweet_url[0] + '&text=' + text_quote + '\');';

        tweet_link.setAttribute( 'onclick', tweet_url );

		// position share div
		var coords = get_selection_coords();
        var scrollpos = document.body.scrollTop;
        coords.y = coords.y+scrollpos;
		
        d.style.left = ((coords.x2-coords.x)/2+coords.x)-(tweetable_selection_width/2) + 'px';
		d.style.top = (coords.y-45) + 'px';
		d.className = 'menu-active';
	}
	else {
		d.className = '';
	}
}

// store width of the share div for positioning
// when an element is set to display:none we can't get the width,
// so we need to add a workaround to fake a visible div
function tweetable_selection_div_width() {
    var tweetable_selection_div = document.getElementById('tweetable-selection');
    tweetable_selection_div.style.visibility = 'hidden';
    tweetable_selection_div.style.display = 'block';
    
    tweetable_selection_width = tweetable_selection_div.offsetWidth;
    
    tweetable_selection_div.removeAttribute('style');
}

// open sharing window
function tweetable_selection_open_win(url) {
	window.open(url,'tweetwindow','width=566,height=350,location=yes,directories=no,channelmode=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no');
	return false;
}

// Listen for mouseup and show share div
document.onmouseup = show_tweetable_selection_div;