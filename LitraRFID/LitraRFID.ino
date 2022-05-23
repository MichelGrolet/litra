#include <M5Stack.h>
#include <Arduino.h>
#include <WiFi.h>
#include <WiFiMulti.h>
#include <HTTPClient.h>

WiFiMulti wifiMulti;
HTTPClient http;

char buffer1[64];      
int count = 0;                    
bool scannagePossible = true; 

void setup() {
  M5.begin(); //Init M5Core. 
  M5.Power.begin(); //Init power  
  wifiMulti.addAP("iPhone de Gabin", "elma9183");  //Storage wifi configuration information.  
  M5.Lcd.print("\nConnecting Wifi...\n"); 
  Serial2.begin(9600, SERIAL_8N1, 16, 17);  //Init serial port 2.  
  if(wifiMulti.run() == WL_CONNECTED){
    M5.Lcd.print("\nSuccessful connection !\n");
    delay(3000);
  } else {
    M5.Lcd.print("\nConnection wifi failed !\n");
    delay(3000);
    exit(0);
  }
  M5.Lcd.clear();
}

void loop() {
  
  M5.Lcd.setCursor(0,0); //Set the cursor at (0,0).  
  M5.update(); //Read the press state of the key.  

  if(wifiMulti.run() != WL_CONNECTED) {
       M5.Lcd.print("Connection wifi lost !\n");
       delay(3000);
       exit(0);
    }

if ((wifiMulti.run() == WL_CONNECTED) && (Serial2.available())){
        
        String url = "http://172.20.10.3/S3C_S12_CHEVALEYRE_CORDURIE_HEMMERLE_GROLET_SACQUARD/public/rfid/";
        String vendeur = "4";
        while(Serial2.available()){ // lecture du la carte    
           buffer1[count++] = Serial2.read(); // ecriture dans le buffer
           if(count == 64)break;
           delay(30);
        }


        String id = String(buffer1);
        id.remove(0,1);
        id.remove(id.length()-1,1);
        String res = (url+id+'-'+vendeur);
        //Serial.write(res, count);
        
        Serial.println(res);
        
        
        M5.Lcd.print("[HTTP] begin CARD...\n");
        
        http.begin(res);// configure traged server and url.  
        clearBufferArray();
        count = 0;
       
        M5.Lcd.print("[HTTP] GET...\n");
        int httpCode = http.GET();  // start connection and send HTTP header. 
         
        if(httpCode > 0) {  // httpCode will be negative on error.  
          M5.Lcd.printf("[HTTP] GET... code: %d\n", httpCode); 
        
          if(httpCode == HTTP_CODE_OK) {  // file found at server.  
            String payload = http.getString();
            M5.Lcd.println(payload);  //Print files read on the server
          }
        } else {
        M5.Lcd.printf("[HTTP] GET... failed, error: %s\n", http.errorToString(httpCode).c_str());
      }
    http.end();
    delay(1000);
    Serial.println("3..");
    delay(1000);
    Serial.println("2..");
    delay(1000);
    Serial.println("1..");
    delay(1000);
    Serial.println("Vous pouvez rescanner !");
    
    M5.Lcd.clear(); //clear the screen.
       
  } else {
    M5.Lcd.print("En attente...");
  }
  delay(1);
}

void clearBufferArray()  // function qui clear le buffer
{
    // clear all index of array with command NULL
    for (int i=0; i<=count; i++)
    {
        buffer1[i]=NULL;
    }                  
}
