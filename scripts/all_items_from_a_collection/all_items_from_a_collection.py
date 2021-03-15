import requests, math, json

ocApiUrl = 'https://oc-index.library.ubc.ca'
apiKey = '' # your api key here
collection = 'darwin'
perPage = 25
offset = 0

# Query the API for the collection item count
collectionUrl = ocApiUrl + '/collections/' + collection + '?api_key=' + apiKey
apiResponse = requests.get(collectionUrl).json()
itemCount = float(apiResponse['data']['items'])

# Figure out how many pages there are
pages = int(math.ceil(itemCount / float(perPage)))

# Loop through collection item pages to get all items
itemIds = []
for x in range(0, pages):
    collectionItemsUrl = ocApiUrl + '/collections/' + collection
    collectionItemsUrl += '/items?limit=' + str(perPage) + '&offset=' + str(offset) + '&api_key=' + apiKey
    offset += 25
    # Get list of 25 items
    apiResponse = requests.get(collectionItemsUrl).json()
    collectionItems = apiResponse['data']
    # Add each item id to the itemIds list
    for collectionItem in collectionItems:
        itemIds.append(collectionItem['_id'])

# Store all the items so we can print them out later
items = []
for itemId in itemIds:
    itemUrl = ocApiUrl + '/collections/' + collection + '/items/' + itemId
    apiResponse = requests.get(itemUrl).json()
    item = apiResponse['data']
    items.append(item)

print(json.dumps(items))
