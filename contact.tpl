
        <div class="container">
            <div class="row">            
                <div class="span6">
                    <div class="bs-docs-input">        
                        <div class="alert alert-error" id="errorDisplay" style="display:none;"></div>
                        <form class="form-horizontal" id="contactForm">                        
                            <div class="control-group">
                                <div class="input-prepend">
                                    <span class="add-on"><i class="icon-user"></i></span><input placeholder="Your Name" class="span5" name="name" id="inputIcon" type="text">
                                </div>                        
                            </div>
                            <div class="control-group">
                                <div class="input-prepend">
                                    <span class="add-on"><i class="icon-envelope"></i></span><input placeholder="Your Email" class="span5" name="email" id="inputIcon" type="text">
                                </div>
                            </div>
                            
                            <div class="control-group">
                                <div class="input-prepend">
                                    <span class="add-on"><i class="icon-edit"></i></span><input placeholder="Subject" class="span5" name="subject" id="inputIcon" type="text">
                                </div>
                            </div>
                            
                            <div class="control-group">
                                <div class="input-prepend">
                                    <span class="add-on"><i class="icon-pencil"></i></span><textarea placeholder="Email text" class="span5" rows="3" name="text"></textarea>
                                </div>                              
                            </div>
                            
                            <div class="control-group" class="span5">                             
                                {$recaptcha}
                            </div>
                            
                            <div class="control-group">
                                <div class="controls">
                                    <button type="button" id="fat-btn"  data-loading-text="Submitting..." class="btn btn-primary"> Submit </button>
                                </div>
                            </div>                          
                        </form>
                    </div>
                </div>                
                <div class="span6">
                    <div class="bs-docs-output">
                        <div id="output">                        
                            <table class="table table-striped table-bordered" id="myTable" style="display:none;">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>                
            </div>            
        </div>
