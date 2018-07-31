/* Initialization part starts here */
var fs = require( 'fs' );
var AWS = require( 'aws-sdk' );
var s3 = new AWS.S3();

//Uncomment for  local only
s3.config.loadFromPath( './secrets/config.js' );

const path = require( 'path' );
//Uncommentt for debugging
//const util = require( 'util' );
const axios = require( 'axios' );

var tph_bucket = '';

const pg = require( 'pg' );

function get_key( key_dir ) {
    fs = require( 'fs' );
    let key = fs.readFileSync( key_dir, 'utf8' );
    return key;
}

const db_client = new pg.Pool( {
    host: '',
    port: 5439,
    user: '',
    password: get_key( path.join( __dirname + '/secrets/', 'redshift' ) ),
    database: 'insights',
    ssl: true
} );
/* Initialization part ends here */


/* Handler function starts here */
//comment next line for local
//exports.handler = (event, context, callback) => {
async function main() {
    //call for data, with expired key request fallover
    let data = await sm_api_call().then( function( response ) {
        if ( response ) {
			console.log('SM API response recieved');
            return response.data.data;
        } else {
            return 'rekey'; //this needs to check the result, not assume
        }
    } ).catch( ( error ) => {
        console.log( 'Axios Error', error );
    } );
	if ( data == 'rekey' ) { //SM token needs a refresh every 10 hrs.
        console.log( 'API Key Expired' );
        await setNewKey().then( function( response ) {
            //rerun the data call after key is updated
			console.log('Performing call after API key update');
            main();
        } ).catch( ( error ) => {
            console.log( 'Key Refresh Error', error );
        } );
    } else if ( data ) {
        //console.log( 'Full Data', util.inspect( data, true, null, true ) );
		console.log('paring data');
        var clean = data_setup( data );
        //start the database calls	
		console.log('Starting DB writes');
        db_write( clean );
    }
}

async function data_setup( data ) {
    //replace funny keys with friendly names, create data out
    let save_data = '';
    for ( let makeReadable of data ) {
        switch ( makeReadable.attributes.dimensions.data_source_id ) {
            case '4a8c55cd-483d-4df7-a4d6-93b98b1d71a3':
               		save_data += 'Facebook Main|shares|';
                    save_data += makeReadable.attributes.metrics.count + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
            case '3af67d78-2a54-4637-8147-6ffd32d0876a':
                    save_data += 'Instagram|saved|';
                    save_data += makeReadable.attributes.metrics.instagram + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                    save_data += 'Instagram|engage|';
                    save_data += makeReadable.attributes.metrics.instagram_engage + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
		      break;
            case '608f9395-27c7-43a3-b890-dd634e859350':
                    save_data +='Youtube|minutes watched|';
                    save_data += makeReadable.attributes.metrics.youtube + '|';
                    save_data +=  makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
            case 'afd879f7-9541-45bc-a82f-e16d3d8922d6':
                    save_data +='Twitter|shares|';
                    save_data += makeReadable.attributes.metrics.count + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
            case '41e31aeb-7a19-4cfb-856e-03e4037f088e':
                    save_data +='Pinterest|shares|';
                    save_data += makeReadable.attributes.metrics["post.shares_count"] + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
            case '0de3d52e-d32f-4a09-8afb-3ce8ed4f76e3':
                    save_data +='Facebook College|shares|';
                    save_data += makeReadable.attributes.metrics.count + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
            case 'e5150269-c36b-458a-abf9-097f127df44a':
                    save_data +='Facebook Deals|shares|';
                    save_data += makeReadable.attributes.metrics.count + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
            case 'f0955992-9c7f-4553-9b81-ae8cabb4c04d':
                    save_data +='Facebook Food|shares|';
                    save_data += makeReadable.attributes.metrics.count + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
            case '629c6b16-e79f-4c54-9bdc-04db856e3efe':
                    save_data +='Facebook Jobs|shares|';
                    save_data += makeReadable.attributes.metrics.count + '|';
                    save_data += makeReadable.attributes.dimensions["post.creation_date.by(day)"].split('T')[0] + '|\n';
                break;
        }
    }
		//console.log(save_data);
        var params ={
            Body: save_data,
            Bucket: tph_bucket,
            Key: 'simplymeasured/new_inserts.json'
        }
        //console.log(params);
        await s3.putObject(params, function(err,data){
            if(data.status==false){
                console.log("Error in Upload JSON file to S3: "+data.error);
            }else{
                console.log("JSON File Uploaded in S3 Bucket.");
            }
    	});

}
function date_to() {
    //swap date_to definitions if you want not-today local
    //var date_to = new Date( '2018-06-20' );
    var date_to = new Date();
    date_to.setDate( date_to.getDate() ); //No Param, we want to end today
    return date_to.toISOString().split( 'T' )[0]; //gives us YYYY-MM-DD
}

function date_from() {
    //swap date_to definitions if you want not-today local
    //var date_from = new Date ( '2018-06-18' );
    var date_from = new Date();
    date_from.setDate( date_from.getDate() - 60 );
    return date_from.toISOString().split( 'T' )[0];
}

var axios_wrapper = async ( url, headers, method, data ) => {
    return await axios( {
            method: method,
            url: url,
            data: data,
            headers: headers,
            timeout: 10000
        } ).then( function( response ) {
            //console.log( 'all data', util.inspect( response, true, null, true ) );
            return response;
        } )
        .catch( async function( error ) {
            if ( error.response ) {
                // The request was made and the server responded with a status code
                // that falls out of the range of 2xx
                //console.log( 'Axios Error Response' ); // this will fire on a stale API key
                //console.log( 'Error Response', util.inspect( error.response, true, null, true ) );
            } else if ( error.request ) {
                // The request was made but no response was received
                //console.log( 'Axios Error Request' );
                //console.log( 'Error Request', util.inspect( error.request, true, null, true ) );
            } else {
                // Something happened in setting up the request that triggered an Error
                //console.log( 'Error Message' );
                //console.log( 'Axios Error Message', util.inspect( error.message, true, null, true ) );
            }
            //console.log( 'Axios Error Config' );
            //console.log( 'Error Config', util.inspect( error, true, null, true ) ); 
        } );
};
async function setNewKey() {
    let refdata = {
        "refresh_token": get_key( path.join( __dirname + '/secrets/', 'sm_refresh_token' ) ),
        "grant_type": "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "account_id": get_key( path.join( __dirname + '/secrets/', 'sm_acct_id' ) )
    };
    let refheaders = {
        'Content-Type': 'application/json',
    };
    var reset = await axios_wrapper( 'https://api.simplymeasured.com/refresh-token', refheaders, 'POST', refdata ).then( function( response ) {
        //write the new api key
        let new_api = response.data.id_token;
		//console.log(new_api);
		var params ={
			Body: new_api,
			Bucket: tph_bucket, 
			Key: 'simplymeasured/sm-api-key'
		};
		//console.log(params);
		s3.putObject(params, function(err,data){
		//console.log('data ',data);
			if(data.status==false){
				console.log("Error in Upload file to S3: "+data.error);
			}else{
				console.log("File Uploaded in S3 Bucket.");
			}
		});
    } ).then( function( response ) {
        //nothing doing here
    } ).catch( ( error ) => {
        console.log( 'Refresh key error ', error );
    } );
    return reset;
}

async function sm_api_call() {
    var params = {
        Bucket: tph_bucket,
        Key: 'simplymeasured/sm-api-key'
    };

	let api_token = await s3.getObject(params).promise();
	api_token = api_token.Body.toString('utf8');
    let acct_id = get_key( path.join( __dirname + '/secrets/', 'sm_acct_id' ) );
    let start_date = date_from();
    let end_date = date_to();
    let url = "https://api.simplymeasured.com";
    url += `/v1/analytics/${acct_id}/posts/metrics`;
    url += '?metrics=count,post.shares_count,';
	  url += 'post.private.lifetime.instagram.saved.as( instagram ),';
	  url += 'post.private.lifetime.instagram.engagements.as( instagram_engage ),';
    url += 'analytics.youtube.estimated_minutes_watched.as( youtube )';
    url += `&filter=post.creation_date.gte( ${start_date} ).lt( ${end_date} )`;
    url += '&dimensions=post.creation_date.by(day),data_source_id,channel';
	  url += '&filter=channel.eq(pinterest,twitter,facebook,youtube,instagram)';
    url += '&tz=America/New_York';
	  //console.log('url: ' + url);
    let method = 'get';
    let data = '';
    let headers = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${api_token}`,
        'Accept-Encoding': 'gzip',
        'gzip': true
    };
    return await axios_wrapper( url, headers, method, data );

}
async function db_query( q, p ) {
    await db_client.query( 'BEGIN' );
    try { //is there a parameter passed?
        if ( typeof( p ) !== 'undefined' ) {
            await db_client.query( q, p );
        } else {
            await db_client.query( q );
        }
        await db_client.query( 'COMMIT' );
    } catch ( err ) {
        await db_client.query( 'ROLLBACK' );
        throw err;
    }
    //console.log( 'DB command: ', res.command );
	//console.log( 'executing SQL' );
    return;
}
async function db_write( save_data ) {
    //create temp table
    var tmp_query = "DROP TABLE IF EXISTS simplymeasured_stage;";
    tmp_query += "CREATE TEMP TABLE simplymeasured_stage ( LIKE insights.simplymeasured );";
    //send it
    //console.log( tmp_query );
    await db_query( tmp_query );


	let from_s3 = "COPY simplymeasured_stage(channel, metric, value, date) ";
	from_s3 += "FROM 's3://" + tph_bucket + "/simplymeasured/new_inserts.json' ";
	from_s3 += "iam_role 'arn:aws:iam::' ";
	
    await db_query( from_s3 );

    //set up the big swap game
    var up_query = "DELETE FROM .simplymeasured USING simplymeasured_stage ";
    up_query += "WHERE .simplymeasured.channel = simplymeasured_stage.channel ";
    up_query += "AND .simplymeasured.metric = simplymeasured_stage.metric ";
    up_query += "AND .simplymeasured.date = TO_DATE(simplymeasured_stage.date, 'YYYY-MM-DD'); ";
	  up_query += "INSERT INTO .simplymeasured (channel, metric, value, date) SELECT channel, metric, value, date FROM simplymeasured_stage";
    //run it
    //console.log( up_query );
    await db_query( up_query );

	await db_client.end();

}
main();
//comment next 2 lines (callback and }) for local
//callback();
//};
/* Handler function ends here */

