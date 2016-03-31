import requests, math, csv

ocApiUrl = 'https://oc-index.library.ubc.ca'
apiKey = 'ac40e6c2cb345593ed1691e0a8b601bba398e42d85f81f893c5ab709cec63c6c'
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

items = []
for itemId in itemIds:
    itemUrl = ocApiUrl + '/collections/' + collection + '/items/' + itemId
    apiResponse = requests.get(itemUrl).json()
    item = apiResponse['data']
    itemStore = dict()
    itemStore['id'] = itemId
    itemStore['title'] = item['Title'][0]['value'].encode('utf8')
    itemStore['description'] = item['Description'][0]['value'].encode('utf8')
    if 'FullText' in item:
        # Note we are ignoring any utf8 encoding errors here
        itemStore['fullText'] = item['FullText'][0]['value'].encode('utf8', errors='ignore')
    else:
        itemStore['fullText'] = ''

    items.append(itemStore)

with open('full-text.csv', 'w+b') as csvfile:
    writer = csv.writer(csvfile, delimiter='~', quotechar='|', quoting=csv.QUOTE_MINIMAL)
    writer.writerow(['ID', 'Title', 'Description', 'Full Text'])
    for item in items:
        writer.writerow([item['id'], item['title'], item['description'], item['fullText']])