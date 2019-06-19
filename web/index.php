<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Group File Creation</title>

    <link rel="stylesheet" type="text/css" href="main.css">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="BigInteger.min.js"></script>
    <script src="bundle.js"></script>
    <script src="web3.min.js"></script>
    <script src="ipfs-http.js"></script>
    <script src="aes.js"></script>
    <script src="FileSaver.min.js"></script>
    <style>
        * {
    box-sizing: border-box;
  }

  /* Create two unequal columns that floats next to each other */
  .column {
    float: left;
    padding: 10px;
    height: 300px; /* Should be removed. Only for demonstration */
  }

  .left {
    width: 25%;
  }

  .right {
    width: 75%;
  }

  /* Clear floats after the columns */
  .row:after {
    content: "";
    display: table;
    clear: both;
  }
  </style>

</head>

<body>

    <div class="row">
        <div id="body1" class="column left">

            <h1>Group Key Encryption</h1>
            <p>Your Wallet: <b><label style="width: 400px" id="currentWalletAddress"></label></b></p>

            Load a text file: <input type="file" id="fileInput" /><br /><br />

            <p>Private Key: <input type="text" id="privateKey" style="width: 400px"  value="Join the contract to generate private key." /></p>
            <p>Notification Email Address: <input type="text"id="notificationEmail" style="width: 400px" value="enterAnEmail@youremail.com" /></p>

            <table>
              <tr>
                <td>
                  <button id="joinSmartContract" onclick="join()">Join Smart Contract</button><br /><br />
                </td>
                <td>
                  <button id="unjoinSmartContract" onclick="unjoin()">Unjoin Smart Contract</button><br /><br />
                </td>
              </tr>
            </table>
            <table>

            <label>Group Key: </label>
            <table>
              <tr>
                <td>
                  <input id="groupKey" style="width: 500px" type="text" value="Enter a group key or generate one.">
                </td>
                <td>
                  <button id="randomEncryptionPhrase" onclick="generateRandomEncryptionPhrase()">Generate</button><br /><br />
                </td>
              </tr>
            </table>
            <label>Group Name: </label>
            <table>
              <tr>
                <td>
                  <input id="groupName" style="width: 500px" type="text" value="Enter your group name.">
                </td>
              </tr>
            </table>
            ---------------------------------------------------------
            <p style="background-color:lightgrey">You've selected <b><label id="currentSelectedUserAmount">0</label></b> users. Click it's button to remove that user as a recipient:</p>
            <ul id='selectedUsers' style='background-color:lightblue;width: 750px'></ul>
            <button id="sendFile" onclick="sendFile()">Send file to selected users</button>
            <label id="sendFileStatus"></label><br /><br /><br />
        </div>
        <center>
            <div class="column right">
                Select the users allowed to decrypt the file:
                <select id="userSelection" style="width: 350px">
                </select>
                <button type="button" onclick="userSelected()">Add</button><br /><br />
                <button type="button" onclick="refreshUserInformation()">Refresh the smart contract user list</button>
                (<label id="currentUsers">0</label> other users not including you loaded)
        </center>
    </div>
    </div>

    <script>
        var myPublicKey; // Stores the public key of the current wallet address during refreshUserInformation()

        var userNumber = 0; // This variable will hold the amount of users currently in the userSelection list.
        var listNumber = 0; //Number of unselected users
        var currentUserAmount = 0;
        var userList = new Map(); // A map of maps. Contains a map of users addresses corresponding to a user and all it's details e.g. (Address, UserMap)
        var selectedUserList = new Map(); //A map containing the selected users allowed to decrypt the file
        var fileBuffer;
        var lastIPFSHash;

        if (typeof web3 !== 'undefined') {
            web3 = new Web3(web3.currentProvider);
        } else {
            // Set Web3 Provider
            web3 = new Web3(new Web3.providers.HttpProvider("localhost:8545"));
        }

        var senderAccount;
        web3.eth.getAccounts((error, accounts) => (senderAccount = accounts[0], document.getElementById('currentWalletAddress').innerHTML = senderAccount)); // Will user the current account selected in MetaMask. Refresh the page to update senderAccount after selected a new MetaMask account.

        const MaxGas = 5000000; // Maximum gas a transaction can spend
        const GasPrice = web3.utils.toWei("250", "gwei"); //Amount of money to pay miners. The higher the value the faster your transaction will be mined.

        // Helper function for asynchronous calls
        async function call(transaction) {
          //let gas = await transaction.estimateGas({from: senderAccount, gasPrice:GasPrice});
          return await transaction.call({from: senderAccount, gasPrice: GasPrice, gas: MaxGas});
        }
        //Helper function for asynchronous transactions
        async function send(transaction) {
          //let gas = await transaction.estimateGas({from: senderAccount, gasPrice: GasPrice});
          return await transaction.send({from: senderAccount, gasPrice: GasPrice, gas: MaxGas});
        }

        async function refreshUserInformation() {
            userNumber = 0;
            document.getElementById("currentUsers").innerHTML = userNumber;
            document.getElementById("currentSelectedUserAmount").innerHTML = 0;
            currentUserAmount = await call(window.contractInstance.methods.getUserCount());

            userList.clear();
            selectedUserList.clear();
            document.getElementById("selectedUsers").innerHTML = "";
            document.getElementById("userSelection").innerHTML = "";

            if (currentUserAmount > 0) { // Don't run this unless user amount is greater than zero
                for (i = 0; i < currentUserAmount; i++) {
                    result = await call(window.contractInstance.methods.getUserIndex(i));
                    if ( result == senderAccount){
                      getMyPublicKey();
                    }
                    if ( result != senderAccount ) {
                      addUserByAddress(result);
                      addUserSelectionItemByText(result);
                      userNumber++;
                      document.getElementById("currentUsers").innerHTML = userNumber;
                    }
                  }
                }
              }

        function addUserSelectionItemByText(text) {
            var x = document.getElementById("userSelection").selectedIndex;
            var y = document.getElementById("userSelection").options;
            try {
                y[y.length] = new Option(text, "newval");
            } catch (e) {
                console.error("Tried to add user selection item but failed. Error: " + e);
            }
        }

        function addUserSelectionItem() {
            var x = document.getElementById("userSelection").selectedIndex;
            var y = document.getElementById("userSelection").options;

            y.add(Option("testing", "newval"));

        }

        function removeUserSelectionItem(x, y) {
            var x = document.getElementById("userSelection").selectedIndex;
            var y = document.getElementById("userSelection").options;
            $("#userSelection option:selected").remove(); // remove a particular item from the list
        }

        function addElement(text) {
            var userLabel = document.createElement("Li");
            userLabel.setAttribute("id", "node" + document.getElementById("selectedUsers").children.length);

            var userLabelButtonRemove = document.createElement("BUTTON");
            userLabelButtonRemove.setAttribute("id", "button" + selectedUserList.size);
            userLabelButtonRemove.setAttribute("class", "ButtonRemoveUser")
            userLabelButtonRemove.innerHTML = text;
            document.getElementById("selectedUsers").appendChild(userLabelButtonRemove);

            selectedUserList.set(text, userList.get(text));
            document.getElementById("currentSelectedUserAmount").innerHTML = selectedUserList.size;
        }

        function removeElement(text) {
            element = document.getElementById(text);
            addUserSelectionItemByText(document.getElementById(text).innerHTML);
            try {
                selectedUserList.delete(document.getElementById(text).innerHTML);
                element.remove();
                document.getElementById("currentSelectedUserAmount").innerHTML = selectedUserList.size;
            } catch (e) {
                console.error("Event misfire. Error: " + e)
            }
        }

        function userSelected() {
            var x = document.getElementById("userSelection").selectedIndex;
            var y = document.getElementById("userSelection").options;
            try {
                addElement(y[x].text);
                removeUserSelectionItem(x, y);
            } catch (e) {
                console.error("There are no items to be removed from the list.");
            }
        }

        // Load a file to the buffer so it can be encrypted with phrase
        document.querySelector('input').addEventListener('change', function() {

            var reader = new FileReader();
            reader.onload = function() {
                var arrayBuffer = this.result,
                    array = new Uint8Array(arrayBuffer);
                    binaryString = String.fromCharCode.apply(null, array);
                    fileBuffer = binaryString;
            }

            reader.readAsArrayBuffer(this.files[0]);

        }, false);


        $("#body1").click(function(event) {
            // Only fire an event if it's an event related to a button. The reason for this is that this event fires for the entire container rather than a specific button. The solution is to find the event value specific to a button rather than it's container.
            if (event.target.id.includes("button", 0)) {
                removeElement(event.target.id);
            } else {}
        });

        var Contract = new web3.eth.Contract([
	{
		"constant": true,
		"inputs": [
			{
				"name": "userAddress",
				"type": "address"
			}
		],
		"name": "getUserContactEmail",
		"outputs": [
			{
				"name": "notificationEmail",
				"type": "string"
			}
		],
		"payable": false,
		"stateMutability": "view",
		"type": "function"
	},
	{
		"constant": true,
		"inputs": [
			{
				"name": "userAddress",
				"type": "address"
			}
		],
		"name": "getUserPublicKey",
		"outputs": [
			{
				"name": "publicKey",
				"type": "string"
			}
		],
		"payable": false,
		"stateMutability": "view",
		"type": "function"
	},
	{
		"constant": true,
		"inputs": [
			{
				"name": "userAddress",
				"type": "address"
			}
		],
		"name": "isUser",
		"outputs": [
			{
				"name": "isIndeed",
				"type": "bool"
			}
		],
		"payable": false,
		"stateMutability": "view",
		"type": "function"
	},
	{
		"constant": false,
		"inputs": [
			{
				"name": "userAddress",
				"type": "address"
			}
		],
		"name": "deleteUser",
		"outputs": [],
		"payable": false,
		"stateMutability": "nonpayable",
		"type": "function"
	},
	{
		"constant": true,
		"inputs": [
			{
				"name": "userAddress",
				"type": "address"
			}
		],
		"name": "getUserByAddress",
		"outputs": [
			{
				"name": "publicKey",
				"type": "string"
			},
			{
				"name": "notificationEmail",
				"type": "string"
			},
			{
				"name": "index",
				"type": "uint256"
			}
		],
		"payable": false,
		"stateMutability": "view",
		"type": "function"
	},
	{
		"constant": false,
		"inputs": [
			{
				"name": "userAddress",
				"type": "address"
			},
			{
				"name": "publicKey",
				"type": "string"
			}
		],
		"name": "updateUserPublicKey",
		"outputs": [],
		"payable": false,
		"stateMutability": "nonpayable",
		"type": "function"
	},
	{
		"constant": false,
		"inputs": [
			{
				"name": "userAddress",
				"type": "address"
			},
			{
				"name": "publicKey",
				"type": "string"
			},
			{
				"name": "notificationEmail",
				"type": "string"
			}
		],
		"name": "insertUser",
		"outputs": [
			{
				"name": "index",
				"type": "uint256"
			}
		],
		"payable": false,
		"stateMutability": "nonpayable",
		"type": "function"
	},
	{
		"constant": true,
		"inputs": [],
		"name": "getUserCount",
		"outputs": [
			{
				"name": "count",
				"type": "uint256"
			}
		],
		"payable": false,
		"stateMutability": "view",
		"type": "function"
	},
	{
		"constant": true,
		"inputs": [
			{
				"name": "index",
				"type": "uint256"
			}
		],
		"name": "getUserIndex",
		"outputs": [
			{
				"name": "userAddress",
				"type": "address"
			}
		],
		"payable": false,
		"stateMutability": "view",
		"type": "function"
	}
], '0x616b61206f0470e4143863f42cf760b74f76eb69', {
            from: senderAccount,
            gas: MaxGas,
            gasPrice: GasPrice,
            network_id: "3"
        });

        window.contractInstance = Contract;

        async function unjoin() {
          let result = await call(window.contractInstance.methods.isUser(senderAccount)).then( result  => {
          if (result){
            document.getElementById("unjoinSmartContract").disabled = true;
            document.getElementById("unjoinSmartContract").innerHTML = "Unjoining...";
            send(window.contractInstance.methods.deleteUser(senderAccount)).then( result => {
              //console.log("Unjoin Sent: " + result);
              refreshUserInformation();
              document.getElementById("unjoinSmartContract").disabled = false;
              document.getElementById("unjoinSmartContract").innerHTML = "Unjoin Smart Contract";
            }).catch( e => {
              console.error('Error unjoining contract: ', e);
            });
          } else {
            window.alert("You haven't joined yet!");
          }
        });

        }

        async function join() {
            let result = await call(window.contractInstance.methods.isUser(senderAccount));
            if (!result) {

              let yourPublicKey = await calculateMyPublicKey();

              let notificationEmail = $("#notificationEmail").val();

              console.log("Sending these four things: " + senderAccount + ", " + yourPublicKey + ", " + notificationEmail);

              document.getElementById("joinSmartContract").disabled = true;
              document.getElementById("joinSmartContract").innerHTML = "Joining...";
              send(window.contractInstance.methods.insertUser(senderAccount, yourPublicKey, notificationEmail)).then( result => {
                //console.log("Sent result: " + result);
                    refreshUserInformation();
                    document.getElementById("joinSmartContract").disabled = false;
                    document.getElementById("joinSmartContract").innerHTML = "Join Smart Contract";
                  }).catch(e => {
                    console.error("Error joining contract: " , e);
                  });
            } else {
              window.alert("You've already joined!");
            }

        }

        // Local function
        function addUserByAddress(address) { // Returns a map object of a user with the user's information
            window.contractInstance.methods.getUserByAddress(address).call(function(err, result) {
                if (err) {
                    console.error(err);
                } else if (result) {

                    userMap = new Map();
                    userMap.set('Address', address);
                    userMap.set('PublicKey', result[0]);
                    userMap.set('notificationEmail', result[1]);
                    userMap.set('Index', result[2])
                    userList.set(address, userMap);

                    return userMap;
                }
            });
        }

        async function getPublicKeyByAddress(address) {
          return await call(window.contractInstance.methods.getUserPublicKey(address));
        }

        async function getMyPublicKey() {
          window.contractInstance.methods.getUserByAddress(senderAccount).call(function(err, result) {
              if (err) {
                  console.error(err);
              } else if (result) {
                myPublicKey = result[0];
              }
          });
        }

        async function getContactEmailByAddress(address) {
          return await call(window.contractInstance.methods.getUserContactAddress(address));
        }

        async function getUserIndex(index) {
            return await call(window.contractInstance.methods.getUserIndex(index));
        }
   // IPFS SECTION BEGIN

        const ipfs = window.IpfsHttpClient("localhost", "5001");

        window.ipfs = ipfs;
        window.ipfsDataHost = "http://localhost:5001/ipfs";

        function addFile(data, notificationList) {
          window.ipfs.add(ipfs.types.Buffer.from(data), function(err, result) {
            if (result) {
              lastIPFSHash = result[0].hash;
              textURL = window.ipfsDataHost + "/" + lastIPFSHash;

               window.alert("Sent [http://localhost/BlockchainDAPP/parser.php?groupfile=" + lastIPFSHash + "] to the following email addresses: " + notificationList);

            } else if (err) {
              console.log("Error reading file. Error: " + err);
            } else {
              console.log("IPFS hash retrieval unsuccessful.")
            }
          });
        }

      // IPFS SECTION END
        //Current RFC 5114 2.1 Standard for G & P converted to decimal
        var P = bigInt(124325339146889384540494091085456630009856882741872806181731279018491820800119460022367403769795008250021191767583423221479185609066059226301250167164084041279837566626881119772675984258163062926954046545485368458404445166682380071370274810671501916789361956272226105723317679562001235501455748016154805420913);
        var G = bigInt(115740200527109164239523414760926155534485715860090261532154107313946218459149402375178179458041461723723231563839316251515439564315555249353831328479173170684416728715378198172203100328308536292821245983596065287318698169565702979765910089654821728828592422299160041156491980943427556153020487552135890973413);

        //var P = 541;
        //var G = 10;
        var myPrivateKey;
        var testNumber;

        function calculateMyPublicKey() {

            myPrivateKey = generateRandom128();

            document.getElementById("privateKey").value = myPrivateKey;

            var privateKeyToDecimal = bigInt(myPrivateKey, 16);

            myPublicKey = bigInt(G).modPow(privateKeyToDecimal, P);

            testNumber = myPublicKey;

            return myPublicKey.toString();
        }

        function calculateSecretFromPublicKey(A, B) {
          console.log("A: " + bigInt(A.toString(), 16).toString() + " & B: " + B.toString());
          var SecretAB = bigInt(B).modPow(bigInt(A.toString(), 16), P);

          return SecretAB.toString();
        }

        function generateRandomEncryptionPhrase() {
          document.getElementById("groupKey").value = generateRandom128();
        }

        function generateRandom128() {
          return CryptoJS.lib.WordArray.random(16); // Random 8 bytes which translates to 16 hex characters
        }

         async function sendFile() {

           var Password = $("#groupKey").val();

           var encryptedFileBuffer = CryptoJS.AES.encrypt(fileBuffer, Password);

           fileBuffer = encryptedFileBuffer;

           var groupFileDetails = new Object();

            groupFileDetails.senderAddress = senderAccount;

            groupFileDetails.senderPublicKey = myPublicKey;

            groupFileDetails.groupName = $("#groupName").val();

            // Recipients[n] and encryptedGroupKey[n] are used to access each user by their index. (Index == n)

            //groupFileDetails.Recipients.length reveals how many recipients are included.
            groupFileDetails.Recipients = [];

            //Store the public key inside the file. This will allow less calls to the Smart Contract. The only time a call to the smart contract is required is to verify that the public key is associated with an ethereum wallet address.
            groupFileDetails.publicKey = [];

            //groupFileDetails.encryptedGroupKey contains the encrypted Group Key. Each item is the Group Key encrypted by the user's unique private channel.
            groupFileDetails.encryptedGroupKey = [];

            // This item will contain the encrypted file data. This is the data that will be decrypted by the Group Key.

            groupFileDetails.encryptedData = fileBuffer.toString();

          var groupKey = $("#groupKey").val(); // The value from the textbox with our secret phrase
          var calculatedSecret;
          var notificationList = "";

          // CURRENTLY WORKING here
          //OBJECTIVE: Get the Shared Secret to work. The decryption didn't work last night. Make sure to SAVE THE PRIVATE KEY for easier processing!
          //Once the shared secret works, save the contents to IPFS and send to parser. If the shared secret mechanics work with the large numbers this prototype is complete.
          //If you cannot get it to work with the large numbers (Precision doesn't work with BIGINT), decrease the size of the numbers.

          console.log("Beginning Iteration");
          selectedUserList.forEach( function (user) {

              var address = user.get("Address");

              if (address != senderAccount){

                groupFileDetails.Recipients.push(address);

                var pubKey = bigInt(user.get("PublicKey"));

                groupFileDetails.publicKey.push(pubKey);

                if (notificationList == ""){
                  notificationList = user.get("notificationEmail");
                } else {
                  notificationList = notificationList.concat("," + user.get("notificationEmail"));
                }

                var privKey = $("#privateKey").val();

                calculatedSecret = calculateSecretFromPublicKey(privKey, pubKey);

                console.log("Calculated Secret: " + calculatedSecret.toString());

                encryptedGroupKey = encrypt(groupKey, calculatedSecret.toString());

                groupFileDetails.encryptedGroupKey.push(encryptedGroupKey);

              }
          });

            console.log(groupFileDetails);
            console.log(JSON.stringify(groupFileDetails));

            await addFile(JSON.stringify(groupFileDetails), notificationList);

        }


       function encrypt(data, password) {

          var encryptedData = CryptoJS.AES.encrypt(data, password);

          return encryptedData.toString();
        }

        function decrypt(data, password) {

          console.log("Decrypting data: " + data.toString());
          console.log("With password: " + password.toString());

          decryptedData = CryptoJS.AES.decrypt(data.toString(), password.toString());


          return web3.utils.toAscii("0x".concat(decryptedData.toString()));
        }

    </script>


</body>

</html>
