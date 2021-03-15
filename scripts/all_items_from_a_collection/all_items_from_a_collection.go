// Author: Yves Beaudoin
package main

import (
	"encoding/json"
	"log"
	"fmt"
	"github.com/christophwitzko/go-curl"
	"strings"
)

const (
    // Replace this public key with your API Key
	_apiKey = ""
    // Define the collection to get all items from
	_collection = "darwin"
)
// Set up the data types for unmarshalling the JSON search results
type _searchResult4Collection struct {
	DATA []struct {
		ID string `json:"_id"`
	} `json:"data"`
}
type _searchResult4ItemData struct {
	DATA interface{} `json:"data"`
}

func main() {
	var (
		collectionItems _searchResult4Collection
		err error
		itemData _searchResult4ItemData
		itemBytes       []byte
		items           [][]byte
	)

	// Get all the item ids for the collection
	curlOutput := curlGet("https://oc-index.library.ubc.ca/collections/" + _collection +
	"/items" +
	"?api_key=" + _apiKey)

	// Store them in a slice we can use
	if err = json.Unmarshal(curlOutput, &collectionItems); err != nil {
		log.Fatalln("\a[json.Unmarshal] ", err)
	}

	// Loop through each item id and store the associated JSON data
	for itemIdx, item := range collectionItems.DATA {
		fmt.Println("Item ID =", item.ID, strings.Repeat("-", 80))
		curlOutput = curlGet("https://oc-index.library.ubc.ca/collections/" + _collection +
		"/items/" + item.ID +
		"?api_key=" + _apiKey)

		if err = json.Unmarshal(curlOutput, &itemData); err != nil {
			log.Fatalln("\a[json.Unmarshal] ", err)
		}
		if itemBytes, err = json.MarshalIndent(itemData, "", "  "); err != nil {
			log.Fatalln("\a[json.MarshalIndent] ", err)
		}
		items = append(items, itemBytes)
		fmt.Println(string(items[itemIdx]))
	}
}
func curlGet(URL string) []byte {
	err, curlOutput, response := curl.Bytes(URL, "method=", "GET", "disablecompression=", true)
	if err != nil {
		log.Fatalln("\a[curlGet] ", err)
	} else if response != nil && response.StatusCode != 200 {
		log.Fatalln("\a[curlGet] response.StatusCode = ", response.StatusCode)
	}
	return curlOutput
}
