from Arlo import Arlo
from datetime import timedelta, date
import datetime
import sys

USERNAME = 'blah@blah.com'
PASSWORD = 'blahblahblah'

try:
	# Instantiating the Arlo object automatically calls Login(), which returns an oAuth token that gets cached.
	# Subsequent successful calls to login will update the oAuth token.
	arlo = Arlo(USERNAME, PASSWORD)
	# At this point you're logged into Arlo.

	today = (date.today()-timedelta(days=0)).strftime("%Y%m%d")
	seven_days_ago = (date.today()-timedelta(days=1)).strftime("%Y%m%d")

	# Get all of the recordings for a date range.
	library = arlo.GetLibrary(today, today)

	# Iterate through the recordings in the library.
	recording = library[0]

	# videofilename = recording['name'] + recording['uniqueId'] + '.mp4'
	videofilename = 'media/video1.mp4'
	# Get video as a chunked stream; this function returns a generator.
	stream = arlo.StreamRecording(recording['presignedContentUrl'])
	with open(videofilename, 'wb') as f:
		for chunk in stream:
			f.write(chunk)
		f.close()

	print('Downloaded video '+videofilename+' from '+recording['createdDate'])

except Exception as e:
    print(e)

