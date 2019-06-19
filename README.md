# GroupKey_SmartContract_Diffie-Hellman
A web tool used to establish Group Key Encryption via Diffie-Hellman by using a Smart Contract

Requirements:
1. The necessary javascript dependencies need to be added.
2. XAMPP needs to be used to create a local web testing environment.
3. MetaMask needs to be installed and configured.
4. userManager.sol or old_userManager.sol needs to be transacted to a distributed blockchain. (Either private, main, or Ropsten (recommended)

The difference between userManager.sol and old_userManager.sol is that old_userManager.sol parses the string inside the smart contract whereas userManager.sol leaves that job to the client. Parsing strings inside a smart contract is operation intensive which translates to more gas which translates to inefficiency. 


