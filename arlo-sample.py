from Arlo import Arlo
from datetime import timedelta, date
import datetime
import sys

def writeVideoFile(arlo, recording, videofilename):
	""" Get video as a chunked stream; this function returns a generator. """
	stream = arlo.StreamRecording(recording['presignedContentUrl'])
	with open(videofilename, 'wb') as f:
		for chunk in stream:
			f.write(chunk)
		f.close()
    return

if __name__ == "__main__":

    USERNAME = 'blah@blah.com'
    PASSWORD = 'blahblahblah'

    try:
        # Instantiating the Arlo object automatically calls Login(), which returns an oAuth token that gets cached.
        # Subsequent successful calls to login will update the oAuth token.
        arlo = Arlo(USERNAME, PASSWORD)

        today = (date.today()-timedelta(days=0)).strftime("%Y%m%d")
        yesterday = (date.today()-timedelta(days=1)).strftime("%Y%m%d")

        # Get all of the recordings for a date range.
        library = arlo.GetLibrary(yesterday, today)

        # Get most recent 4 videos in the library.
        for num in range(4):
            recording = library[num]
            vfname = "media/video" + str(num+1) + ".mp4"
            writeVideoFile(arlo, recording, vfname)

        print("Successfully downloaded and updated 4 videos")

    except Exception as e:
        print(e)
