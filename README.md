# Biitstrap

> This is a submission for Bitcoin Association Hackathon.

Video Preview: https://youtu.be/Ygv1BCiWdGA

Biitstrap is a technique for bootstrapping a communication channel via a blockchain which is explained here: https://github.com/prayer0/biitstrap/blob/master/doc/README.md. It's useful for every scenario where peers don't know each other's connection details.

Biitstrap Messenger is a little PoC messenger that employs this technique. Peers exchanges their public keys by broadcasting their connection details to the blockchain encryptedly to find each other's location. Then they start communicating off-chain.

This is a PoC for demonstraing the technique which may be useful and applicable for many areas such as EE2E messaging, SSH, IoT, remote control, IP-transactions etc.

Devices can be interconnected by using only public keys thanks to this technique.

## Requirements

Requirements: PHP, Composer, MySQL
Open Ports: 80, 8080 

## Setup

You need to install the application to 2 separate machines. Then, we'll try to let machines find each other even though they don't know each others ip address.

Clone repository:
```
git clone https://github.com/prayer0/biitstrap.git
```

Install dependencies:
```
cd /path_to_project
composer install
```

Edit DB config:
```
cd /path_to_project
sudo nano .env 
```

Initial settings:
```
cd /path_to_project
php biitstrap setup
```

It's a webpage so you need to run a HTTP server. You can use your favorite one or use the built-in server: 
```
cd /path_to_project
php bin/console server:run <ip>:80
```

Run WebSocket server for live communication:
```
cd /path_to_project
php biitstrap server
```

## Usage

1. Visit first machine's ip with your browser.
2. Visit second machine's ip with another browser.
3. Copy second machine's public key.
4. On first machine's webpage, paste it and invite him to off-chain communication.
5. On second machine's webpage, there should be and invite alert, confirm it.
6. After this point, two machines should be able to communicate through websockets if port configurations are ok. 

## Notes

- Sorry for shitcoding.
- Some features like challenge signatures that is defined in the technical draft are avoided for gaining speed for this implementation.
- App doesn't work if funding wallet goes out of balance. It can be found in `config/services.yaml` and can be refunded.
- Port 80 and 8080 should be accessible.
- Machines find each other but I couldn't find time to test off-chain messaging on live server. And it may not work on local machine properly.
- Please use two different (non-local) machines.
- Wait all js files (there are too many) to be loaded before interaction.