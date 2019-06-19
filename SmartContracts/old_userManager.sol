pragma solidity ^0.4.18;
import './StringUtils.sol'; // String utilities for parsing the file upload string

contract userManager {
    
    using strings for *; // For accessing the strings library from StringUtils.sol
    
   // Process the upload string and forward to recipients
   

   string private testStorage;
   
   function sendFile(string uploadString, address[] recipients) public{
       
          var s = uploadString.toSlice();
          var delim = ",".toSlice();
          var count = s.count(delim) + 1;
          var parts = new string[](count);
          
          for(uint i = 0; i < count; i++) {
              parts[i] = s.split(delim).toString(); // Split the entire uploadString into ',' comma slices
         }
            testStorage = parts[0];
           // part[0] is always the returnAddress
          
          for (i = 0; i < recipients.length; i++) { // Load the addresses from the recipient array of addresses
              userStructs[recipients[i]].returnAddress = parts[0];
              userStructs[recipients[i]].fileInbox = parts[i+1]; // Leave i <= recipients.length because part[0] is always the returnAddress
          }

    }
    
    function set(string newString) public {
        testStorage = newString;
    }
    
    function get() public view returns (string returnString){
        return testStorage;
    }

  struct UserStruct {
    string publicKey;
    string fileInbox;
    string returnAddress; 
    uint index;
  }
  
  mapping(address => UserStruct) private userStructs;
  address[] private userIndex;
  
  function isUser(address userAddress) public view returns(bool isIndeed) {
    if(userIndex.length == 0) return false;
    return (userIndex[userStructs[userAddress].index] == userAddress);
  }

  function insertUser(address userAddress, string publicKey, string fileInbox, string returnAddress) public returns(uint index) {
    if(isUser(userAddress)) revert(); // Prevent users that are already joined from joining again thereby creating duplicate addresses
    userStructs[userAddress].publicKey = publicKey;
    userStructs[userAddress].fileInbox = fileInbox;
    userStructs[userAddress].index = userIndex.push(userAddress)-1;
    userStructs[userAddress].returnAddress = returnAddress;

    return userIndex.length-1;
  }

  
  function getUserByAddress(address userAddress) public view returns(string publicKey, string fileInbox, uint index, string returnAddress) {
    if(!isUser(userAddress)) revert(); // If the user address isn't in userStructs, don't return the information.
    return(
      userStructs[userAddress].publicKey, 
      userStructs[userAddress].fileInbox, 
      userStructs[userAddress].index,
      userStructs[userAddress].returnAddress);
  }
  
  function getUserPublicKey(address userAddress) public view returns(string publicKey) {
      return(userStructs[userAddress].publicKey);
  }
  
  function getUserFileInbox(address userAddress) public view returns(string fileInbox) {
      return(userStructs[userAddress].fileInbox);
  }
  
  function getUserReturnAddress(address userAddress) public view returns(string returnAddress) {
      return(userStructs[userAddress].returnAddress);
  }
  
  function updateUserPublicKey(address userAddress, string publicKey) public returns(bool success) {
    if(!isUser(userAddress)) revert(); 
    userStructs[userAddress].publicKey = publicKey;

    return true;
  }
  
  function updateFileStatus(address userAddress, string fileInbox) 
    public
    returns(bool success) 
  {
    if(!isUser(userAddress)) revert(); 
    userStructs[userAddress].fileInbox = fileInbox;

    return true;
  }

  function getUserCount() 
    public
    view
    returns(uint count)
  {
    return userIndex.length;
  }

  function getUserIndex(uint index)
    public
    view
    returns(address userAddress)
  {
    return userIndex[index];
  }
  
  function deleteUser(address userAddress) public {
    if(!isUser(userAddress)) revert(); 
    uint rowToDelete = userStructs[userAddress].index;
    address keyToMove = userIndex[userIndex.length-1];
    userIndex[rowToDelete] = keyToMove;
    userStructs[keyToMove].index = rowToDelete; 
    userIndex.length--;
  }

}