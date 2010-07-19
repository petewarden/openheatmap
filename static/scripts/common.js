var _kmq = _kmq || [];
function _kms(u){
    setTimeout(function(){
        var s = document.createElement('script'); var f = document.getElementsByTagName('script')[0]; s.type = 'text/javascript'; s.async = true;
        s.src = u; f.parentNode.insertBefore(s, f);
    }, 1);
}
_kms('//i.kissmetrics.com/i.js');_kms('//doug1izaerwt3.cloudfront.net/7e2410e274ede95acd5551c3260ff85fba13f337.1.js');

var g_hasSetResultMessage = false;

function onUploadComplete(responseString64)
{
    var messageHtml = '';
    var wasSuccessful = false;
    if (responseString64 === '')
    {
        messageHtml = 'There was an unknown error trying to upload this CSV file. Please contact <a href="mailto:pete@petewarden.com">pete@petewarden.com</a> to report this bug if you see this repeatedly';
        wasSuccessful = false;
    }
    else
    {
        var responseString = $.base64.decode(responseString64);
        
        var response = eval('('+responseString+')');

        wasSuccessful = ((response.output_id!=='')&&
            (response.errors.length===0));
                        
        var errors = response.errors;
        
        if (errors.length>0)
        {
            messageHtml += '<i>';
            messageHtml += '<b>';
            messageHtml += 'Errors:';
            messageHtml += '</b>';
            messageHtml += '</i>';
            messageHtml += '<br>';
            
            messageHtml += formatErrors(errors);
        }
        
        var warnings = response.warnings;
        
        if (warnings.length>0)
        {
            messageHtml += '<i>';
            messageHtml += '<b>';
            messageHtml += 'Warnings:';
            messageHtml += '</b>';
            messageHtml += '</i>';
            messageHtml += '<br>';

            messageHtml += formatErrors(warnings);
        }
        
    }

    // enable upload button
    $('#upload_button')
    .html('<i>Upload</i>');

    if ((messageHtml!=='')||g_hasSetResultMessage)
    {
        $('#result_message').html(messageHtml);
        g_hasSetResultMessage = true;
    }

    if (wasSuccessful)
    {
        $.cookie('last_output_id', response.output_id);
        $.cookie('last_editing_password', response.editing_password);

        $('#next_button').show();
        $('#upload_button').hide();
        
        $('#guidance_message').html('Your data was uploaded successfully, you can move onto the next step.');
    }
    else
    {
        $('#next_button').hide();
        $('#upload_button').show();

        $('#guidance_message').html('There were some errors uploading the data. You can try uploading again once you\'ve investigated them.');
    }
}

function formatErrors(errors)
{
    var messageMap = {};

    var result = '';

    for (var index in errors)
    {
        var error = errors[index];
        var message = error.message;
        var row = error.row;

        if (row<=0)
        {
            result += message;
            result += '<br>';
        }
        else
        {
            if (typeof messageMap[message] === 'undefined')
                messageMap[message] = [];
                
            messageMap[message].push(row);
        }
    }

    for (var message in messageMap)
    {
        var rows = messageMap[message];
        
        var prefix = '';
        if (rows.length===1)
        {
            prefix += 'Row ';
            prefix += rows[0];
        }
        else
        {
            rows.sort(function(a,b){return a - b});
            
            var sequences = [];
            var currentSequence = null;
            for (var rowIndex in rows)
            {
                var currentRow = rows[rowIndex];
                
                if ((currentSequence===null)||
                    (currentRow!=(currentSequence.end+1)))
                {
                    if (currentSequence!==null)
                        sequences.push(currentSequence);
                    
                    currentSequence = {
                        start: currentRow,
                        end: currentRow
                    };
                }
                else
                {
                    currentSequence.end = currentRow;
                }
            
            }
            
            if (currentSequence!==null)
                sequences.push(currentSequence);
            
            var sequenceStrings = [];
            for (var sequenceIndex in sequences)
            {
                var sequence = sequences[sequenceIndex];
                var start = sequence.start;
                var end = sequence.end;
                
                var sequenceString = '';
                if (start===end)
                    sequenceString += start;
                else
                    sequenceString += start+'-'+end;
                
                sequenceStrings.push(sequenceString);
            }
            
            prefix += 'Rows ';
            prefix += sequenceStrings.join(', ');
        }
        
        result += prefix;
        result += ': ';
        result += message;
        result += '<br>';
    }
    
    return result;
}

function htmlspecialchars(string)
{ 
    return $('<span>').text(string).html()
}
