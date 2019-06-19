pragma solidity ^0.5.1; // Use Version 0.5.1

contract userManager { // Smart Contract User List
    
  struct UserStruct { // A user object
    string publicKey; // User Public Key
    string notificationEmail; // User Notification Email Address
    uint index; // User Index n
  }
  
  mapping(address => UserStruct) private userStructs; // Creating a mapping
  address[] private userIndex; // Create an array of addresses
  
  function isUser(address userAddress) public view returns(bool isIndeed) { // Checks to see if a user has already joined
    if(userIndex.length == 0) return false;
    return (userIndex[userStructs[userAddress].index] == userAddress);
  }

  function insertUser(address userAddress, string memory publicKey, string memory notificationEmail) public returns(uint index) { // joinSmartContract() calls to insert new user
    if(isUser(userAddress)) revert(); // Prevent users that are already joined from joining again thereby creating duplicate addresses
    userStructs[userAddress].publicKey = publicKey;
    userStructs[userAddress].notificationEmail = notificationEmail;
    userStructs[userAddress].index = userIndex.push(userAddress)-1;

    return userIndex.length-1;
  }
  
  function getUserByAddress(address userAddress) public view returns(string memory publicKey, string memory notificationEmail, uint index) { // Get user by Wallet Address
    if(!isUser(userAddress)) revert(); // If the user address isn't in userStructs, don't return the information.
    return(
      userStructs[userAddress].publicKey,
      userStructs[userAddress].notificationEmail,
      userStructs[userAddress].index);
  }
  
  function getUserPublicKey(address userAddress) public view returns(string memory publicKey) { // Get a user Public Key by Wallet Address
      return(userStructs[userAddress].publicKey);
  }
  
  function getUserContactEmail(address userAddress) public view returns(string memory notificationEmail) { // Get a user Email Address by Wallet Address
      return(userStructs[userAddress].notificationEmail);
  }
  
  function updateUserPublicKey(address userAddress, string memory publicKey) public { // Update a user Public Key by Wallet Address
    if(!isUser(userAddress)) revert(); 
    userStructs[userAddress].publicKey = publicKey;
  }
  
  function getUserCount() public view // Get the amount of joined users
    returns(uint count) {
        return userIndex.length;
  }

  function getUserIndex(uint index) public view // Get a user by an index 
    returns(address userAddress){
        return userIndex[index];
  }
  
  function deleteUser(address userAddress) public { // Delete a user from the Smart Contract User List
    if(!isUser(userAddress)) revert(); 
    uint rowToDelete = userStructs[userAddress].index;
    address keyToMove = userIndex[userIndex.length-1];
    userIndex[rowToDelete] = keyToMove;
    userStructs[keyToMove].index = rowToDelete; 
    userIndex.length--;
  }

}