module Jekyll
  class UnpublishedPageGenerator < Generator
    safe false

    def replace(filepath, regexp, *args, &block)
      content = File.read(filepath).gsub(regexp, *args, &block)
      File.open(filepath, 'wb') { |file| file.write(content) }
    end

    def mark_drafts
      @site.pages.each {|page|
        if page.dir == '/drafts'
          page.data['draft'] = true
        else
          page.data['draft'] = false
        end
      }
    end

    def generate(site)
      @site = site
      mark_drafts

      @files = Dir["_publish/*"]
      @files.each_with_index { |f,i|
        now = DateTime.now.strftime("%Y-%m-%d %H:%M:%S")
        replace(f, /^date: unpublished/mi) { |match| "date: \"" + now + "\"" }
        now = Date.today.strftime("%Y-%m-%d")
        File.rename(f, "_posts/#{now}-#{File.basename(f)}")
      }
    end
  end
end
